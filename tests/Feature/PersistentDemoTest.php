<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersistentDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_demo_is_authenticated_and_isolated(): void
    {
        $this->get('/demo/database')->assertRedirect('/login');
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->get('/demo/database')->assertOk();
        $this->post('/demo/database/change', [
            'sample_key' => 'sample-dragon', 'quantity' => 5, 'direction' => 'in', 'reason' => 'Test receipt',
        ])->assertRedirect('/demo/database');
        $this->assertDatabaseHas('demo_stock', ['user_id' => $first->id, 'sample_key' => 'sample-dragon', 'quantity' => 17]);

        $this->actingAs($second)->get('/demo/database')->assertOk();
        $this->assertDatabaseHas('demo_stock', ['user_id' => $second->id, 'sample_key' => 'sample-dragon', 'quantity' => 12]);

        $this->post('/demo/database/reset')->assertRedirect('/demo/database');
        $this->assertDatabaseHas('demo_stock', ['user_id' => $first->id, 'sample_key' => 'sample-dragon', 'quantity' => 17]);
    }

    public function test_demo_rejects_negative_stock_and_reset_restores_sample(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/demo/database/change', [
            'sample_key' => 'sample-dragon', 'quantity' => 13, 'direction' => 'out', 'reason' => 'Damage',
        ])->assertSessionHasErrors('quantity');
        $this->post('/demo/database/reset')->assertRedirect('/demo/database');
        $this->assertDatabaseHas('demo_stock', ['user_id' => $user->id, 'sample_key' => 'sample-dragon', 'quantity' => 12]);
    }
}
