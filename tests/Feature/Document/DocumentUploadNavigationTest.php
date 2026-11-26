<?php

namespace Tests\Feature\Document;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentUploadNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function officer(): User
    {
        return User::factory()->create(['role' => 'officer']);
    }

    public function test_admin_sees_document_upload_sidebar_item(): void
    {
        $this->actingAs($this->admin())->get('/')->assertSee('Document Upload');
    }

    public function test_officer_does_not_see_document_upload_sidebar_item(): void
    {
        $this->actingAs($this->officer())->get('/')->assertDontSee('Document Upload');
    }

    public function test_selecting_item_sets_document_upload_section(): void
    {
        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'document-upload')
            ->assertSet('activeView', 'document-upload');
    }

    public function test_right_side_renders_upload_component(): void
    {
        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'document-upload')
            ->assertSee('Upload Regulatory Document');
    }

    public function test_sidebar_and_layout_remain_present(): void
    {
        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'document-upload')
            ->assertSee('Gov Services')
            ->assertSee('Spend & Governance', escape: false);
    }

    public function test_existing_sections_still_work(): void
    {
        Livewire::actingAs($this->admin())
            ->test('copilot-chat')
            ->set('activeView', 'corpus')
            ->assertSet('activeView', 'corpus')
            ->assertSee('Regulatory Knowledge Base');
    }

    public function test_officer_cannot_bypass_by_manipulating_section(): void
    {
        Livewire::actingAs($this->officer())
            ->test('copilot-chat')
            ->set('activeView', 'document-upload')
            ->assertSet('activeView', 'consultation')
            ->assertDontSee('Upload Regulatory Document');
    }

    public function test_obsolete_standalone_route_is_gone(): void
    {
        $this->actingAs($this->admin())->get('/documents/upload')->assertNotFound();
    }
}
