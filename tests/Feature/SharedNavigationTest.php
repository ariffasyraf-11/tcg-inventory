<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_workspace_pages_render_shared_navigation(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            '/dashboard', '/catalog', '/inventory', '/sales',
            '/imports/preview', '/demo/database', '/settings',
        ] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('Workspace navigation')
                ->assertSee('Log out')
                ->assertSee('CSV preview');
        }
    }

    public function test_guests_cannot_view_sidebar_pages(): void
    {
        foreach ([
            '/dashboard', '/catalog', '/inventory', '/sales',
            '/imports/preview', '/demo/database', '/settings',
        ] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }
}
