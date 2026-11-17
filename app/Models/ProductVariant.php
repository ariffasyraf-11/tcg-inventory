<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'condition', 'language', 'finish', 'card_number', 'sealed_format',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
