<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class StockMovement extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Stock movements cannot be edited.');
        });
        static::deleting(function (): void {
            throw new LogicException('Stock movements cannot be deleted.');
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }
}
