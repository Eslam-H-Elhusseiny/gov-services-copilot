<?php

namespace Tests\Feature;

use App\Application\Copilot\Contracts\CaseRepository;
use App\Domain\Copilot\WorkflowState;
use App\Infrastructure\Copilot\EloquentCaseRepository;
use App\Models\AuditLog;
use App\Models\ServiceCase;
use App\Models\User;
use App\Models\WorkflowRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class EloquentCaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): CaseRepository
    {
        return app(CaseRepository::class);
    }

    private function officer(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    private function makePendingRun(User $user, string $draft = 'Draft body'): array
    {
        $caseId = $this->repo()->createConsultation($user->id, 'Case title', 'Situation text', 'reasoning', 'GSC-CORR-TEST');

        $run = WorkflowRun::first();
        $run->update([
            'current_state' => WorkflowState::PendingApproval->value,
            'draft_response' => $draft,
        ]);

        return ['case_id' => $caseId, 'run_id' => $run->id];
    }

    public function test_container_resolves_case_repository_to_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentCaseRepository::class, $this->repo());
    }

    public function test_create_consultation_creates_case_message_received_run_and_audit(): void
    {
        $user = $this->officer();

        $caseId = $this->repo()->createConsultation($user->id, 'My title', 'My situation', 'reasoning', 'GSC-CORR-ABC');

        $this->assertDatabaseHas('cases', [
            'id' => $caseId,
            'user_id' => $user->id,
            'status' => 'pending_approval',
        ]);
        $this->assertDatabaseHas('case_messages', [
            'case_id' => $caseId,
            'sender_type' => 'officer',
            'content' => 'My situation',
        ]);
        $this->assertDatabaseHas('workflow_runs', [
            'case_id' => $caseId,
            'current_state' => WorkflowState::Received->value,
            'run_type' => 'officer',
        ]);

        $run = WorkflowRun::first();
        $this->assertNull($run->draft_response);
        $this->assertNull($run->started_at);
        $this->assertNull($run->completed_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'RUN_MULTI_AGENT_WORKFLOW',
            'subject_id' => $caseId,
            'correlation_id' => 'GSC-CORR-ABC',
        ]);
    }

    public function test_create_consultation_rolls_back_all_records_on_failure(): void
    {
        $user = $this->officer();

        AuditLog::creating(function () {
            throw new RuntimeException('forced failure during audit insert');
        });

        try {
            $this->repo()->createConsultation($user->id, 'Title', 'Situation', 'reasoning', 'GSC-CORR-FAIL');
            $this->fail('Expected createConsultation to throw.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(0, ServiceCase::count());
        $this->assertSame(0, DB::table('case_messages')->count());
        $this->assertSame(0, WorkflowRun::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_find_run_for_user_returns_data_for_owner(): void
    {
        $user = $this->officer();
        ['run_id' => $runId, 'case_id' => $caseId] = $this->makePendingRun($user, 'Hello draft');

        $result = $this->repo()->findRunForUser($runId, $user->id);

        $this->assertNotNull($result);
        $this->assertSame($runId, $result['id']);
        $this->assertSame($caseId, $result['case_id']);
        $this->assertSame(WorkflowState::PendingApproval, $result['state']);
        $this->assertSame('Hello draft', $result['draft_response']);
    }

    public function test_find_run_for_user_returns_null_for_another_user(): void
    {
        $owner = $this->officer();
        $other = $this->officer();
        ['run_id' => $runId] = $this->makePendingRun($owner);

        $this->assertNull($this->repo()->findRunForUser($runId, $other->id));
    }

    public function test_approve_succeeds_for_owned_pending_run(): void
    {
        $user = $this->officer();
        ['run_id' => $runId, 'case_id' => $caseId] = $this->makePendingRun($user, 'Original draft');

        $this->repo()->approveRun($runId, $user->id, null);

        $run = WorkflowRun::find($runId);
        $this->assertSame(WorkflowState::Approved->value, $run->current_state);
        $this->assertSame('Original draft', $run->final_response);
        $this->assertNotNull($run->completed_at);
        $this->assertSame('approved', ServiceCase::find($caseId)->status);

        $this->assertDatabaseHas('approvals', [
            'workflow_run_id' => $runId,
            'officer_id' => $user->id,
            'decision' => 'approve',
        ]);
    }

    public function test_approve_with_edited_content_stores_edit_and_approve(): void
    {
        $user = $this->officer();
        ['run_id' => $runId] = $this->makePendingRun($user, 'Original draft');

        $this->repo()->approveRun($runId, $user->id, 'Edited draft');

        $run = WorkflowRun::find($runId);
        $this->assertSame('Edited draft', $run->final_response);

        $this->assertDatabaseHas('approvals', [
            'workflow_run_id' => $runId,
            'decision' => 'edit_and_approve',
            'edited_content' => 'Edited draft',
            'approved_content' => 'Edited draft',
        ]);
    }

    public function test_reject_succeeds_for_owned_pending_run(): void
    {
        $user = $this->officer();
        ['run_id' => $runId, 'case_id' => $caseId] = $this->makePendingRun($user);

        $this->repo()->rejectRun($runId, $user->id, 'Conflicting law');

        $run = WorkflowRun::find($runId);
        $this->assertSame(WorkflowState::Rejected->value, $run->current_state);
        $this->assertNotNull($run->completed_at);
        $this->assertSame('rejected', ServiceCase::find($caseId)->status);

        $this->assertDatabaseHas('approvals', [
            'workflow_run_id' => $runId,
            'decision' => 'reject',
            'review_comment' => 'Conflicting law',
        ]);
    }

    public function test_approve_and_reject_are_rejected_for_runs_in_other_states(): void
    {
        $user = $this->officer();
        $caseId = $this->repo()->createConsultation($user->id, 'Title', 'Situation', 'reasoning', 'GSC-CORR-X');
        $runId = WorkflowRun::first()->id;

        try {
            $this->repo()->approveRun($runId, $user->id, null);
            $this->fail('Expected approve to throw for RECEIVED run.');
        } catch (RuntimeException) {
        }

        try {
            $this->repo()->rejectRun($runId, $user->id, 'nope');
            $this->fail('Expected reject to throw for RECEIVED run.');
        } catch (RuntimeException) {
        }

        $this->assertSame(WorkflowState::Received->value, WorkflowRun::find($runId)->current_state);
        $this->assertSame('pending_approval', ServiceCase::find($caseId)->status);
    }

    public function test_duplicate_approval_does_not_create_duplicate_records(): void
    {
        $user = $this->officer();
        ['run_id' => $runId] = $this->makePendingRun($user);

        $this->repo()->approveRun($runId, $user->id, null);

        try {
            $this->repo()->approveRun($runId, $user->id, null);
            $this->fail('Expected second approve to throw.');
        } catch (RuntimeException) {
        }

        $this->assertSame(1, DB::table('approvals')->where('workflow_run_id', $runId)->count());
    }

    public function test_cross_user_approve_reject_and_delete_are_rejected(): void
    {
        $owner = $this->officer();
        $intruder = $this->officer();
        ['run_id' => $runId, 'case_id' => $caseId] = $this->makePendingRun($owner);

        try {
            $this->repo()->approveRun($runId, $intruder->id, null);
            $this->fail('Expected cross-user approve to throw.');
        } catch (RuntimeException) {
        }

        try {
            $this->repo()->rejectRun($runId, $intruder->id, 'nope');
            $this->fail('Expected cross-user reject to throw.');
        } catch (RuntimeException) {
        }

        $this->repo()->deleteCaseForUser($caseId, $intruder->id);
        $this->assertDatabaseHas('cases', ['id' => $caseId, 'user_id' => $owner->id]);

        $this->assertSame(WorkflowState::PendingApproval->value, WorkflowRun::find($runId)->current_state);
    }

    public function test_delete_case_removes_dependent_records_respecting_foreign_keys(): void
    {
        $user = $this->officer();
        ['run_id' => $runId, 'case_id' => $caseId] = $this->makePendingRun($user);

        // Create an approval so the RESTRICT FK is exercised.
        $this->repo()->approveRun($runId, $user->id, null);

        // Create a model_pricing row (FK target for llm_calls).
        $pricingId = DB::table('model_pricing')->insertGetId([
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'input_price_per_1k' => 0.0,
            'output_price_per_1k' => 0.0,
            'effective_from' => now()->toDateString(),
            'created_at' => now(),
        ]);

        DB::table('workflow_steps')->insert([
            'workflow_run_id' => $runId,
            'step_type' => 'agent',
            'name' => 'eligibility',
            'status' => 'completed',
            'created_at' => now(),
        ]);

        DB::table('llm_calls')->insert([
            'workflow_run_id' => $runId,
            'user_id' => $user->id,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'input_price_per_1k' => 0.0,
            'output_price_per_1k' => 0.0,
            'model_pricing_id' => $pricingId,
            'created_at' => now(),
        ]);

        $this->repo()->deleteCaseForUser($caseId, $user->id);

        $this->assertSame(0, ServiceCase::count());
        $this->assertSame(0, DB::table('case_messages')->count());
        $this->assertSame(0, WorkflowRun::count());
        $this->assertSame(0, DB::table('approvals')->count());
        $this->assertSame(0, DB::table('workflow_steps')->count());
        $this->assertSame(0, DB::table('llm_calls')->count());
    }
}
