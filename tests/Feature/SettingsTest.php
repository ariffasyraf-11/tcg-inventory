<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_require_login(): void
    {
        $this->get('/settings')->assertRedirect('/login');
        $this->patch('/settings/profile')->assertRedirect('/login');
    }

    public function test_owner_can_update_own_profile_and_workspace(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/settings')->assertOk();
        $this->patch('/settings/profile', ['name' => 'Updated seller'])->assertRedirect('/settings');
        $this->patch('/settings/workspace', ['name' => 'Updated store'])->assertRedirect('/settings');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated seller']);
        $this->assertDatabaseHas('workspaces', ['owner_user_id' => $user->id, 'name' => 'Updated store']);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'StrongPass123!']);
        $this->actingAs($user)->patch('/settings/password', [
            'current_password' => 'wrong', 'password' => 'NewStrongPass123!', 'password_confirmation' => 'NewStrongPass123!',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('StrongPass123!', $user->fresh()->password));

        $this->patch('/settings/password', [
            'current_password' => 'StrongPass123!', 'password' => 'NewStrongPass123!', 'password_confirmation' => 'NewStrongPass123!',
        ])->assertRedirect('/settings');

        $this->assertTrue(Hash::check('NewStrongPass123!', $user->fresh()->password));
    }

    public function test_sku_preference_is_private_and_demo_is_read_only(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Owner']);

        $this->actingAs($other)->patch('/settings/single-sku', ['single_sku_enabled' => 0])
            ->assertRedirect('/settings');
        $this->assertTrue(Workspace::where('owner_user_id', $owner->id)->first()->single_sku_enabled);

        $demo = User::factory()->create();
        Workspace::create(['owner_user_id' => $demo->id, 'name' => 'Demo', 'is_demo' => true]);
        $this->actingAs($demo)->patch('/settings/single-sku', ['single_sku_enabled' => 0])->assertForbidden();
    }

    public function test_owner_cannot_change_another_owners_workspace(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Private store']);

        $this->actingAs($other)->patch('/settings/workspace', ['name' => 'My store'])->assertRedirect('/settings');

        $this->assertDatabaseHas('workspaces', ['owner_user_id' => $owner->id, 'name' => 'Private store']);
    }
}
