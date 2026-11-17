<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkuPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_sku_requirement_on_dedicated_page(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->get('/settings/sku-permissions')->assertOk()->assertSee('SKU requirement');
        $this->patch('/settings/sku-permissions', ['single_sku_enabled' => 0])->assertRedirect('/settings/sku-permissions');
        $this->assertDatabaseHas('workspaces', ['owner_user_id' => $owner->id, 'single_sku_enabled' => 0]);
    }

    public function test_sku_settings_are_not_shared_with_other_workspaces(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Private', 'single_sku_enabled' => true]);
        $this->actingAs($other)->patch('/settings/sku-permissions', ['single_sku_enabled' => 0])->assertRedirect('/settings/sku-permissions');
        $this->assertTrue((bool) $workspace->fresh()->single_sku_enabled);
    }

    public function test_invalid_sku_preference_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->patch('/settings/sku-permissions', ['single_sku_enabled' => 'invalid'])->assertSessionHasErrors('single_sku_enabled');
    }
}
