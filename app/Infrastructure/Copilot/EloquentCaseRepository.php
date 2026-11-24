<?php

namespace App\Infrastructure\Copilot;

use App\Application\Copilot\Contracts\CaseRepository;
use App\Domain\Copilot\WorkflowState;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\CaseMessage;
use App\Models\ServiceCase;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EloquentCaseRepository implements CaseRepository
{
    public function createConsultation(
        int $userId,
        string $title,
        string $situation,
        string $model,
        string $correlationId
    ): int {
        return DB::transaction(function () use ($userId, $title, $situation, $model, $correlationId) {
            $case = ServiceCase::create([
                'user_id' => $userId,
                'title' => $title,
                'situation' => $situation,
                'status' => 'pending_approval',
            ]);

            CaseMessage::create([
                'case_id' => $case->id,
                'sender_type' => 'officer',
                'content' => $situation,
                'citations' => null,
                'created_at' => now(),
            ]);

            $runUuid = (string) Str::uuid();

            WorkflowRun::create([
                'case_id' => $case->id,
                'run_uuid' => $runUuid,
                'correlation_id' => $correlationId,
                'run_type' => 'officer',
                'current_state' => WorkflowState::Received->value,
                'draft_response' => null,
                'final_response' => null,
                'estimated_tokens' => 0,
                'reserved_tokens' => 0,
                'started_at' => null,
                'completed_at' => null,
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'RUN_MULTI_AGENT_WORKFLOW',
                'subject_type' => 'gov_case',
                'subject_id' => $case->id,
                'correlation_id' => $correlationId,
                'details' => [
                    'run_uuid' => $runUuid,
                    'tokens_consumed' => 0,
                    'model' => $model,
                ],
                'created_at' => now(),
            ]);

            return $case->id;
        });
    }

    public function findRunForUser(int $runId, int $userId): ?array
    {
        $run = WorkflowRun::with('serviceCase')
            ->whereHas('serviceCase', fn ($query) => $query->where('user_id', $userId))
            ->find($runId);

        if ($run === null) {
            return null;
        }

        $state = WorkflowState::tryFrom($run->current_state);

        if ($state === null) {
            throw new RuntimeException(sprintf('Invalid workflow state "%s" stored for run %d.', $run->current_state, $run->id));
        }

        return [
            'id' => $run->id,
            'case_id' => $run->case_id,
            'state' => $state,
            'draft_response' => $run->draft_response,
        ];
    }

    public function approveRun(
        int $runId,
        int $officerId,
        ?string $editedContent
    ): void {
        DB::transaction(function () use ($runId, $officerId, $editedContent) {
            $run = WorkflowRun::with('serviceCase')
                ->whereHas('serviceCase', fn ($query) => $query->where('user_id', $officerId))
                ->lockForUpdate()
                ->find($runId);

            if ($run === null) {
                throw new RuntimeException('Workflow run not found or not authorized for this officer.');
            }

            if ($run->current_state !== WorkflowState::PendingApproval->value) {
                throw new RuntimeException('Only workflow runs pending approval can be approved.');
            }

            $isEdit = $editedContent !== null;
            $finalResponse = $editedContent ?? $run->draft_response;

            $run->update([
                'current_state' => WorkflowState::Approved->value,
                'final_response' => $finalResponse,
                'completed_at' => now(),
            ]);

            $run->serviceCase->update([
                'status' => 'approved',
            ]);

            Approval::create([
                'workflow_run_id' => $run->id,
                'officer_id' => $officerId,
                'decision' => $isEdit ? 'edit_and_approve' : 'approve',
                'edited_content' => $editedContent,
                'approved_content' => $finalResponse,
                'review_comment' => $isEdit
                    ? 'Officer made adjustments before granting official approval.'
                    : 'Officer verified compliance with statutory provisions and granted full authorization.',
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $officerId,
                'action' => $isEdit ? 'EDIT_AND_APPROVE_RESPONSE' : 'APPROVE_OFFICIAL_RESPONSE',
                'subject_type' => 'workflow_run',
                'subject_id' => $run->id,
                'correlation_id' => $run->correlation_id,
                'details' => [
                    'run_uuid' => $run->run_uuid,
                    'case_id' => $run->case_id,
                    'decision' => $isEdit ? 'edit_and_approve' : 'approve',
                ],
                'created_at' => now(),
            ]);
        });
    }

    public function rejectRun(
        int $runId,
        int $officerId,
        string $reason
    ): void {
        DB::transaction(function () use ($runId, $officerId, $reason) {
            $run = WorkflowRun::with('serviceCase')
                ->whereHas('serviceCase', fn ($query) => $query->where('user_id', $officerId))
                ->lockForUpdate()
                ->find($runId);

            if ($run === null) {
                throw new RuntimeException('Workflow run not found or not authorized for this officer.');
            }

            if ($run->current_state !== WorkflowState::PendingApproval->value) {
                throw new RuntimeException('Only workflow runs pending approval can be rejected.');
            }

            $run->update([
                'current_state' => WorkflowState::Rejected->value,
                'completed_at' => now(),
            ]);

            $run->serviceCase->update([
                'status' => 'rejected',
            ]);

            Approval::create([
                'workflow_run_id' => $run->id,
                'officer_id' => $officerId,
                'decision' => 'reject',
                'edited_content' => null,
                'approved_content' => null,
                'review_comment' => $reason,
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $officerId,
                'action' => 'REJECT_OFFICIAL_RESPONSE',
                'subject_type' => 'workflow_run',
                'subject_id' => $run->id,
                'correlation_id' => $run->correlation_id,
                'details' => [
                    'run_uuid' => $run->run_uuid,
                    'case_id' => $run->case_id,
                    'decision' => 'reject',
                    'reason' => $reason,
                ],
                'created_at' => now(),
            ]);
        });
    }

    public function deleteCaseForUser(int $caseId, int $userId): void
    {
        DB::transaction(function () use ($caseId, $userId) {
            $case = ServiceCase::where('user_id', $userId)->find($caseId);

            if ($case === null) {
                return;
            }

            $case->workflowRuns()->each(function (WorkflowRun $run) {
                $run->approvals()->delete();
                $run->delete();
            });

            $case->messages()->delete();
            $case->delete();
        });
    }
}
