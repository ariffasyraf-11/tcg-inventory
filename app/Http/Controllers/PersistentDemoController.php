<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersistentDemoController extends Controller
{
    private const SAMPLES = ['sample-dragon' => 12, 'sample-mage' => 4, 'starter-deck' => 8, 'booster-box' => 2];

    public function index(Request $request): View
    {
        $this->seed($request->user()->id);

        return view('demo.persistent', [
            'items' => DB::table('demo_stock')->where('user_id', $request->user()->id)->orderBy('id')->get(),
            'movements' => DB::table('demo_stock_movements')->where('user_id', $request->user()->id)->orderByDesc('id')->limit(30)->get(),
        ]);
    }

    public function change(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sample_key' => ['required', 'in:sample-dragon,sample-mage,starter-deck,booster-box'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'direction' => ['required', 'in:in,out'],
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $this->seed($request->user()->id);
            $item = DB::table('demo_stock')->where('user_id', $request->user()->id)
                ->where('sample_key', $data['sample_key'])->lockForUpdate()->first();
            $delta = (int) $data['quantity'] * ($data['direction'] === 'out' ? -1 : 1);

            if ($item->quantity + $delta < 0) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient demo stock.']);
            }

            DB::table('demo_stock')->where('id', $item->id)->update(['quantity' => $item->quantity + $delta, 'updated_at' => now()]);
            DB::table('demo_stock_movements')->insert([
                'user_id' => $request->user()->id, 'sample_key' => $data['sample_key'],
                'signed_quantity' => $delta, 'reason' => $data['reason'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);

        return to_route('demo.persistent')->with('status', 'Sample stock updated.');
    }

    public function reset(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            DB::table('demo_stock_movements')->where('user_id', $request->user()->id)->delete();
            DB::table('demo_stock')->where('user_id', $request->user()->id)->delete();
            $this->seed($request->user()->id);
        });

        return to_route('demo.persistent')->with('status', 'Private demo reset.');
    }

    private function seed(int $userId): void
    {
        foreach (self::SAMPLES as $key => $quantity) {
            DB::table('demo_stock')->insertOrIgnore([
                'user_id' => $userId, 'sample_key' => $key, 'quantity' => $quantity,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
