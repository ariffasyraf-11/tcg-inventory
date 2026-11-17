<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_register_and_reach_catalog(): void
    {
        $this->post('/register', [
            'name' => 'Seller',
            'email' => 'seller@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect('/catalog');

        $this->assertAuthenticated();
        $this->get('/catalog')->assertOk();
    }

    public function test_authenticated_catalog_shows_a_post_logout_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/catalog')
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('Log out');
    }

    public function test_login_regenerates_session_and_logout_ends_session(): void
    {
        $user = User::factory()->create(['password' => 'StrongPass123!']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect('/catalog');

        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/catalog')->assertRedirect('/login');
    }
}
