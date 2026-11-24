<?php

namespace App\Livewire;

use App\Application\Copilot\ApproveResponse;
use App\Application\Copilot\DeleteCase;
use App\Application\Copilot\RejectResponse;
use App\Application\Copilot\SubmitConsultation;
use App\Models\AuditLog;
use App\Models\ServiceCase;
use App\Models\UserBudget;
use App\Models\WorkflowRun;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use RuntimeException;

class CopilotChat extends Component
{
    public ?int $activeCaseId = null;

    public string $situationInput = '';

    public string $searchQuery = '';

    public string $selectedModel = 'reasoning';

    public string $activeView = 'consultation';

    public bool $isEditModalOpen = false;

    public string $editedResponseText = '';

    public ?int $editingRunId = null;

    public bool $isRejectModalOpen = false;

    public string $rejectionComment = '';

    public ?int $rejectingRunId = null;

    public ?array $selectedCitation = null;

    public bool $isCitationModalOpen = false;

    private const VIEWS = ['consultation', 'approvals', 'corpus', 'usage'];

    public function mount(): void
    {
        $firstCase = $this->baseCasesQuery()->first();

        if ($firstCase) {
            $this->activeCaseId = $firstCase->id;
        }
    }

    public function updatedActiveView(string $value): void
    {
        if (! in_array($value, self::VIEWS, true)) {
            $this->activeView = 'consultation';
        }
    }

    public function getActiveCaseProperty(): ?ServiceCase
    {
        if (! $this->activeCaseId) {
            return null;
        }

        return $this->baseCasesQuery()
            ->with(['messages', 'workflowRuns.approvals.officer', 'workflowRuns.latestApproval'])
            ->find($this->activeCaseId);
    }

    public function getCasesProperty()
    {
        $query = $this->baseCasesQuery()
            ->withCount('messages')
            ->orderBy('updated_at', 'desc');

        if (! empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->searchQuery.'%')
                    ->orWhere('situation', 'like', '%'.$this->searchQuery.'%');
            });
        }

        return $query->get();
    }

    public function getUserBudgetProperty(): ?UserBudget
    {
        return Auth::user()?->latestBudget;
    }

    public function getPendingApprovalsCountProperty(): int
    {
        $query = WorkflowRun::where('current_state', 'PENDING_APPROVAL');

        if (! $this->isAdmin()) {
            $query->whereHas('serviceCase', fn ($q) => $q->where('user_id', Auth::id()));
        }

        return $query->count();
    }

    public function getPendingRunsProperty()
    {
        $query = WorkflowRun::with(['serviceCase.user', 'latestApproval'])
            ->where('current_state', 'PENDING_APPROVAL')
            ->latest();

        if (! $this->isAdmin()) {
            $query->whereHas('serviceCase', fn ($q) => $q->where('user_id', Auth::id()));
        }

        return $query->get();
    }

    public function getAuditLogsProperty()
    {
        $query = AuditLog::with('user')->latest()->take(10);

        if (! $this->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query->get();
    }

    public function newCase(): void
    {
        $this->activeCaseId = null;
        $this->situationInput = '';
        $this->activeView = 'consultation';
    }

    public function selectCase(int $id): void
    {
        if (! $this->baseCasesQuery()->find($id)) {
            $this->dispatch('toast', type: 'error', message: 'Consultation not found.');

            return;
        }

        $this->activeCaseId = $id;
        $this->activeView = 'consultation';
    }

    public function applyPrompt(string $prompt): void
    {
        $this->situationInput = $prompt;
    }

    public function submitSituation(SubmitConsultation $submit): void
    {
        $data = $this->validate([
            'situationInput' => 'required|min:10|string',
        ]);

        try {
            Gate::authorize('create', ServiceCase::class);
        } catch (AuthorizationException) {
            $this->dispatch('toast', type: 'error', message: 'You are not authorized to create consultations.');

            return;
        }

        $caseId = $submit->handle(Auth::id(), $data['situationInput'], $this->selectedModel);

        $this->activeCaseId = $caseId;
        $this->situationInput = '';
        $this->dispatch('toast', type: 'info', message: 'Consultation created. Awaiting AI response generation.');
    }

    public function approveResponse(int $runId, ApproveResponse $approve): void
    {
        $run = WorkflowRun::with('serviceCase')->find($runId);

        if ($run === null) {
            $this->dispatch('toast', type: 'error', message: 'Workflow run not found.');

            return;
        }

        try {
            Gate::authorize('approve', $run);
        } catch (AuthorizationException) {
            $this->dispatch('toast', type: 'error', message: 'You are not authorized to approve this response.');

            return;
        }

        try {
            $approve->handle($runId, Auth::id());
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: 'Official response approved and recorded in the audit registry.');
    }

    public function openEditModal(int $runId): void
    {
        $run = WorkflowRun::with('serviceCase')
            ->whereHas('serviceCase', fn ($q) => $q->where('user_id', Auth::id()))
            ->find($runId);

        if ($run === null) {
            $this->dispatch('toast', type: 'error', message: 'Workflow run not found.');

            return;
        }

        if ($run->current_state !== 'PENDING_APPROVAL') {
            $this->dispatch('toast', type: 'error', message: 'Only runs pending approval can be edited.');

            return;
        }

        $this->editingRunId = $run->id;
        $this->editedResponseText = $run->draft_response ?? '';
        $this->isEditModalOpen = true;
    }

    public function saveEditedApproval(ApproveResponse $approve): void
    {
        $this->validate([
            'editedResponseText' => 'required|min:20',
        ]);

        $run = WorkflowRun::with('serviceCase')->find($this->editingRunId);

        if ($run === null) {
            $this->dispatch('toast', type: 'error', message: 'Workflow run not found.');

            return;
        }

        try {
            Gate::authorize('approve', $run);
        } catch (AuthorizationException) {
            $this->dispatch('toast', type: 'error', message: 'You are not authorized to approve this response.');

            return;
        }

        try {
            $approve->handle($run->id, Auth::id(), $this->editedResponseText);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->isEditModalOpen = false;
        $this->editingRunId = null;
        $this->dispatch('toast', type: 'success', message: 'Response edited, approved, and logged.');
    }

    public function openRejectModal(int $runId): void
    {
        $run = WorkflowRun::with('serviceCase')
            ->whereHas('serviceCase', fn ($q) => $q->where('user_id', Auth::id()))
            ->find($runId);

        if ($run === null) {
            $this->dispatch('toast', type: 'error', message: 'Workflow run not found.');

            return;
        }

        if ($run->current_state !== 'PENDING_APPROVAL') {
            $this->dispatch('toast', type: 'error', message: 'Only runs pending approval can be rejected.');

            return;
        }

        $this->rejectingRunId = $run->id;
        $this->rejectionComment = '';
        $this->isRejectModalOpen = true;
    }

    public function submitRejection(RejectResponse $reject): void
    {
        $this->validate([
            'rejectionComment' => 'required|min:5',
        ]);

        $run = WorkflowRun::with('serviceCase')->find($this->rejectingRunId);

        if ($run === null) {
            $this->dispatch('toast', type: 'error', message: 'Workflow run not found.');

            return;
        }

        try {
            Gate::authorize('reject', $run);
        } catch (AuthorizationException) {
            $this->dispatch('toast', type: 'error', message: 'You are not authorized to reject this response.');

            return;
        }

        try {
            $reject->handle($run->id, Auth::id(), $this->rejectionComment);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->isRejectModalOpen = false;
        $this->rejectingRunId = null;
        $this->dispatch('toast', type: 'warning', message: 'Proposed draft rejected and logged for review.');
    }

    public function inspectCitation(array $citation): void
    {
        $this->selectedCitation = $citation;
        $this->isCitationModalOpen = true;
    }

    public function deleteCase(int $id, DeleteCase $delete): void
    {
        $case = $this->baseCasesQuery()->find($id);

        if ($case === null) {
            $this->dispatch('toast', type: 'error', message: 'Consultation not found.');

            return;
        }

        try {
            Gate::authorize('delete', $case);
        } catch (AuthorizationException) {
            $this->dispatch('toast', type: 'error', message: 'You are not authorized to delete this consultation.');

            return;
        }

        $delete->handle($id, Auth::id());

        if ($this->activeCaseId === $id) {
            $this->activeCaseId = $this->baseCasesQuery()->first()?->id;
        }

        $this->dispatch('toast', type: 'neutral', message: 'Consultation deleted.');
    }

    public function render()
    {
        return view('livewire.copilot-chat');
    }

    private function isAdmin(): bool
    {
        return (bool) (Auth::user()?->isAdmin());
    }

    private function baseCasesQuery()
    {
        $query = ServiceCase::query();

        if (! $this->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }
}
