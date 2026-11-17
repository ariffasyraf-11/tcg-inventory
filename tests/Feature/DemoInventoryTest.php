<?php

namespace Tests\Feature;

use Tests\TestCase;

class DemoInventoryTest extends TestCase
{
    public function test_local_demo_displays_fictional_stock(): void
    {
        $this->get('/demo')
            ->assertOk()
            ->assertSee('Sample Dragon #001')
            ->assertSee('Booster Box B');
    }

    public function test_receipt_adjustment_and_reset_are_session_scoped(): void
    {
        $this->post('/demo/receive', [
            'item_id' => 'sample-dragon',
            'quantity' => 5,
            'unit_cost' => '2.50',
        ])->assertRedirect('/demo');

        $this->assertSame(17, session('tcg_demo_inventory')['items']['sample-dragon']['quantity']);

        $this->post('/demo/adjust', [
            'item_id' => 'sample-dragon',
            'direction' => 'out',
            'quantity' => 1,
            'reason' => 'Damaged sample card',
        ])->assertRedirect('/demo');

        $this->assertSame(16, session('tcg_demo_inventory')['items']['sample-dragon']['quantity']);
        $this->assertCount(2, session('tcg_demo_inventory')['movements']);

        $this->post('/demo/reset')->assertRedirect('/demo');
        $this->get('/demo')->assertSee('Sample Dragon #001');
        $this->assertNull(session('tcg_demo_inventory'));
    }

    public function test_demo_rejects_negative_stock_and_missing_reason(): void
    {
        $this->from('/demo')->post('/demo/adjust', [
            'item_id' => 'sample-dragon',
            'direction' => 'out',
            'quantity' => 20,
            'reason' => 'Damaged',
        ])->assertSessionHasErrors('quantity');

        $this->from('/demo')->post('/demo/adjust', [
            'item_id' => 'sample-dragon',
            'direction' => 'out',
            'quantity' => 1,
            'reason' => '',
        ])->assertSessionHasErrors('reason');
    }

    public function test_demo_routes_are_not_available_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->get('/demo')->assertNotFound();
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }
}
