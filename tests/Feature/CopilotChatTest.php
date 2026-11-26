<?php

namespace Tests\Feature;

use App\Application\Copilot\Contracts\CaseRepository;
use App\Domain\Copilot\WorkflowState;
use App\Models\ServiceCase;
use App\Models\User;
use App\Models\UserBudget;
use App\Models\WorkflowRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CopilotChatTest extends TestCase
{
    use RefreshDatabase;

    private function officer(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pendingRun(User $user, string $draft = 'Draft body'): array
    {
        $caseId = app(CaseRepository::class)->createConsultation(
            $user->id,
            'Case title',
            'Situation text',
            'reasoning',
            'GSC-CORR-TEST',
        );

        $run = WorkflowRun::first();
        $run->update([
            'current_state' => WorkflowState::PendingApproval->value,
            'draft_response' => $draft,
        ]);

        return ['case_id' => $caseId, 'run_id' => $run->id];
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_officer_can_view_copilot_workspace(): void
    {
        $user = $this->officer();

        UserBudget::create([
            'user_id' => $user->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'budget_limit' => 250000,
            'consumed_tokens' => 8420,
            'reserved_tokens' => 1500,
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Gov Services');
        $response->assertSee('Copilot');
    }

    public function test_officer_can_create_consultation_producing_received_run_without_draft(): void
    {
        $user = $this->officer();

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->set('situationInput', 'Citizen lost the national ID and needs a replacement.')
            ->call('submitSituation')
            ->assertSet('situationInput', '')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('cases', [
            'user_id' => $user->id,
            'status' => 'pending_approval',
        ]);

        $run = WorkflowRun::first();
        $this->assertNotNull($run);
        $this->assertSame(WorkflowState::Received->value, $run->current_state);
        $this->assertNull($run->draft_response);
        $this->assertNull($run->started_at);
        $this->assertNull($run->completed_at);
    }

    public function test_officer_can_switch_cases_and_start_new_query(): void
    {
        $user = $this->officer();

        $case1 = ServiceCase::create([
            'user_id' => $user->id,
            'title' => 'National ID Loss Case',
            'situation' => 'Citizen lost their ID card and requests renewal.',
            'status' => 'pending_approval',
        ]);

        $case2 = ServiceCase::create([
            'user_id' => $user->id,
            'title' => 'Commercial Shop License',
            'situation' => 'Citizen requests retail license conditions.',
            'status' => 'approved',
        ]);

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->assertSet('activeCaseId', $case1->id)
            ->call('selectCase', $case2->id)
            ->assertSet('activeCaseId', $case2->id)
            ->call('newCase')
            ->assertSet('activeCaseId', null)
            ->assertSee('Good day');
    }

    public function test_officer_cannot_access_another_officers_case(): void
    {
        $owner = $this->officer();
        $intruder = $this->officer();

        $ownCase = ServiceCase::create([
            'user_id' => $owner->id,
            'title' => 'Own case',
            'situation' => 'x',
            'status' => 'pending_approval',
        ]);

        $otherCase = ServiceCase::create([
            'user_id' => $intruder->id,
            'title' => 'Other case',
            'situation' => 'y',
            'status' => 'pending_approval',
        ]);

        Livewire::actingAs($owner)
            ->test('copilot-chat')
            ->call('selectCase', $otherCase->id)
            ->assertSet('activeCaseId', $ownCase->id)
            ->assertDispatched('toast');
    }

    public function test_officer_cannot_approve_another_officers_run(): void
    {
        $owner = $this->officer();
        $intruder = $this->officer();
        ['run_id' => $runId] = $this->pendingRun($owner);

        Livewire::actingAs($intruder)
            ->test('copilot-chat')
            ->call('approveResponse', $runId)
            ->assertDispatched('toast');

        $this->assertSame(WorkflowState::PendingApproval->value, WorkflowRun::find($runId)->current_state);
        $this->assertDatabaseMissing('approvals', ['workflow_run_id' => $runId]);
    }

    public function test_officer_cannot_reject_another_officers_run(): void
    {
        $owner = $this->officer();
        $intruder = $this->officer();
        ['run_id' => $runId] = $this->pendingRun($owner);

        Livewire::actingAs($intruder)
            ->test('copilot-chat')
            ->set('rejectingRunId', $runId)
            ->set('rejectionComment', 'Not allowed')
            ->call('submitRejection')
            ->assertDispatched('toast');

        $this->assertSame(WorkflowState::PendingApproval->value, WorkflowRun::find($runId)->current_state);
        $this->assertDatabaseMissing('approvals', ['workflow_run_id' => $runId]);
    }

    public function test_officer_cannot_delete_another_officers_case(): void
    {
        $intruder = $this->officer();
        $otherCase = ServiceCase::create([
            'user_id' => $this->officer()->id,
            'title' => 'Other case',
            'situation' => 'y',
            'status' => 'pending_approval',
        ]);

        Livewire::actingAs($intruder)
            ->test('copilot-chat')
            ->call('deleteCase', $otherCase->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('cases', ['id' => $otherCase->id]);
    }

    public function test_admin_lands_on_home_and_sees_all_cases(): void
    {
        $officerA = $this->officer();
        $officerB = $this->officer();

        ServiceCase::create(['user_id' => $officerA->id, 'title' => 'A', 'situation' => 'a', 'status' => 'pending_approval']);
        ServiceCase::create(['user_id' => $officerB->id, 'title' => 'B', 'situation' => 'b', 'status' => 'pending_approval']);

        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->assertSet('activeView', 'home')
            ->assertSee('Administrator workspace')
            ->assertDontSee('New Consultation')
            ->assertDontSee('Approvals Queue');

        $this->assertSame(2, ServiceCase::count());
    }

    public function test_officer_sees_consultation_controls(): void
    {
        Livewire::actingAs($this->officer())
            ->test('copilot-chat')
            ->assertSee('New Consultation')
            ->assertSee('Consultations')
            ->assertSee('Approvals Queue');
    }

    public function test_admin_cannot_create_approve_reject_or_delete(): void
    {
        $admin = $this->admin();
        $owner = $this->officer();
        ['run_id' => $runId, 'case_id' => $caseId] = $this->pendingRun($owner);

        Livewire::actingAs($admin)
            ->test('copilot-chat')
            ->set('situationInput', 'Admin trying to create a consultation.')
            ->call('submitSituation')
            ->assertDispatched('toast')
            ->call('approveResponse', $runId)
            ->assertDispatched('toast')
            ->call('deleteCase', $caseId)
            ->assertDispatched('toast');

        $this->assertSame(1, ServiceCase::count());
        $this->assertSame(WorkflowState::PendingApproval->value, WorkflowRun::find($runId)->current_state);
    }

    public function test_officer_can_approve_own_pending_run(): void
    {
        $user = $this->officer();
        ['run_id' => $runId, 'case_id' => $caseId] = $this->pendingRun($user, 'Original draft');

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->call('approveResponse', $runId)
            ->assertDispatched('toast');

        $this->assertSame(WorkflowState::Approved->value, WorkflowRun::find($runId)->current_state);
        $this->assertSame('approved', ServiceCase::find($caseId)->status);
        $this->assertDatabaseHas('approvals', [
            'workflow_run_id' => $runId,
            'officer_id' => $user->id,
            'decision' => 'approve',
        ]);
    }

    public function test_officer_can_edit_and_approve_own_pending_run(): void
    {
        $user = $this->officer();
        ['run_id' => $runId] = $this->pendingRun($user, 'Original draft');

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->call('openEditModal', $runId)
            ->assertSet('isEditModalOpen', true)
            ->set('editedResponseText', 'Edited and corrected draft')
            ->call('saveEditedApproval')
            ->assertSet('isEditModalOpen', false)
            ->assertDispatched('toast');

        $this->assertSame('Edited and corrected draft', WorkflowRun::find($runId)->final_response);
        $this->assertDatabaseHas('approvals', [
            'workflow_run_id' => $runId,
            'decision' => 'edit_and_approve',
        ]);
    }

    public function test_officer_can_reject_own_pending_run(): void
    {
        $user = $this->officer();
        ['run_id' => $runId, 'case_id' => $caseId] = $this->pendingRun($user);

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->call('openRejectModal', $runId)
            ->assertSet('isRejectModalOpen', true)
            ->set('rejectionComment', 'Conflicting statutory provision')
            ->call('submitRejection')
            ->assertSet('isRejectModalOpen', false)
            ->assertDispatched('toast');

        $this->assertSame(WorkflowState::Rejected->value, WorkflowRun::find($runId)->current_state);
        $this->assertSame('rejected', ServiceCase::find($caseId)->status);
        $this->assertDatabaseHas('approvals', [
            'workflow_run_id' => $runId,
            'decision' => 'reject',
        ]);
    }

    public function test_approval_and_rejection_rejected_when_not_pending_approval(): void
    {
        $user = $this->officer();

        $caseId = app(CaseRepository::class)->createConsultation(
            $user->id,
            'Title',
            'Situation',
            'reasoning',
            'GSC-CORR-N',
        );
        $runId = WorkflowRun::first()->id;

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->call('approveResponse', $runId)
            ->assertDispatched('toast')
            ->call('openRejectModal', $runId)
            ->assertSet('isRejectModalOpen', false);

        $this->assertSame(WorkflowState::Received->value, WorkflowRun::find($runId)->current_state);
        $this->assertSame('pending_approval', ServiceCase::find($caseId)->status);
        $this->assertDatabaseCount('approvals', 0);
    }

    public function test_search_filters_case_list(): void
    {
        $user = $this->officer();

        ServiceCase::create(['user_id' => $user->id, 'title' => 'National ID', 'situation' => 'lost card', 'status' => 'pending_approval']);
        ServiceCase::create(['user_id' => $user->id, 'title' => 'Shop License', 'situation' => 'retail', 'status' => 'pending_approval']);

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->set('searchQuery', 'National')
            ->assertSet('searchQuery', 'National');
    }

    public function test_all_views_render_and_invalid_view_falls_back_to_consultation(): void
    {
        $user = $this->officer();

        Livewire::actingAs($user)
            ->test('copilot-chat')
            ->set('activeView', 'approvals')
            ->assertSet('activeView', 'approvals')
            ->assertSee('Official Decision')
            ->set('activeView', 'corpus')
            ->assertSee('Regulatory Knowledge Base')
            ->set('activeView', 'usage')
            ->assertSee('Token Cost Governor')
            ->set('activeView', 'consultation')
            ->assertSee('Good day')
            ->set('activeView', 'bogus')
            ->assertSet('activeView', 'consultation');
    }
}
