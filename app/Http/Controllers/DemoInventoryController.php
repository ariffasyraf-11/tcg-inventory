<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DemoInventoryController extends Controller
{
    private const SESSION_KEY = 'tcg_demo_inventory';

    public function index(Request $request): View
    {
        $this->ensureLocal();
        $state = $this->state($request);

        return view('demo.inventory', [
            'items' => array_values($state['items']),
            'movements' => $state['movements'],
            'totalUnits' => array_sum(array_column($state['items'], 'quantity')),
            'lowStockCount' => count(array_filter(
                $state['items'],
                fn (array $item): bool => $item['quantity'] <= $item['minimum_stock']
            )),
        ]);
    }

    public function receive(Request $request): RedirectResponse
    {
        $this->ensureLocal();
        $data = $request->validate([
            'item_id' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'unit_cost' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
        ]);

        $state = $this->state($request);
        $item = $this->findItem($state, $data['item_id']);
        $quantity = (int) $data['quantity'];

        $state['items'][$data['item_id']]['quantity'] += $quantity;
        $this->addMovement($state, $item, 'Receipt', $quantity, 'Sample cost: '.$data['unit_cost']);
        $request->session()->put(self::SESSION_KEY, $state);

        return to_route('demo.index')->with('status', "Received {$quantity} unit(s) of {$item['name']}.");
    }

    public function adjust(Request $request): RedirectResponse
    {
        $this->ensureLocal();
        $data = $request->validate([
            'item_id' => ['required', 'string'],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        $state = $this->state($request);
        $item = $this->findItem($state, $data['item_id']);
        $delta = (int) $data['quantity'] * ($data['direction'] === 'out' ? -1 : 1);

        if ($item['quantity'] + $delta < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'This adjustment exceeds the available demo stock.',
            ]);
        }

        $state['items'][$data['item_id']]['quantity'] += $delta;
        $this->addMovement($state, $item, 'Adjustment', $delta, $data['reason']);
        $request->session()->put(self::SESSION_KEY, $state);

        return to_route('demo.index')->with('status', "Adjusted {$item['name']} by {$delta} unit(s).");
    }

    public function reset(Request $request): RedirectResponse
    {
        $this->ensureLocal();
        $request->session()->forget(self::SESSION_KEY);

        return to_route('demo.index')->with('status', 'Demo restored to its original sample quantities.');
    }

    private function ensureLocal(): void
    {
        abort_unless(app()->environment('local', 'testing'), 404);
    }

    private function findItem(array $state, string $id): array
    {
        if (! array_key_exists($id, $state['items'])) {
            throw ValidationException::withMessages([
                'item_id' => 'Choose a valid sample product.',
            ]);
        }

        return $state['items'][$id];
    }

    private function addMovement(array &$state, array $item, string $type, int $delta, string $reason): void
    {
        array_unshift($state['movements'], [
            'product' => $item['name'],
            'type' => $type,
            'change' => $delta,
            'reason' => $reason,
            'at' => now()->format('Y-m-d H:i:s'),
        ]);

        $state['movements'] = array_slice($state['movements'], 0, 30);
    }

    private function state(Request $request): array
    {
        return $request->session()->get(self::SESSION_KEY, [
            'items' => [
                'sample-dragon' => [
                    'id' => 'sample-dragon', 'name' => 'Sample Dragon #001',
                    'type' => 'Single', 'detail' => 'Near Mint · English · Regular',
                    'quantity' => 12, 'minimum_stock' => 3,
                ],
                'sample-mage' => [
                    'id' => 'sample-mage', 'name' => 'Sample Mage #014',
                    'type' => 'Single', 'detail' => 'Lightly Played · English · Foil',
                    'quantity' => 4, 'minimum_stock' => 3,
                ],
                'starter-deck' => [
                    'id' => 'starter-deck', 'name' => 'Starter Deck A',
                    'type' => 'Sealed', 'detail' => 'Starter deck · Factory sealed',
                    'quantity' => 8, 'minimum_stock' => 2,
                ],
                'booster-box' => [
                    'id' => 'booster-box', 'name' => 'Booster Box B',
                    'type' => 'Sealed', 'detail' => 'Booster box · Factory sealed',
                    'quantity' => 2, 'minimum_stock' => 3,
                ],
            ],
            'movements' => [],
        ]);
    }
}
