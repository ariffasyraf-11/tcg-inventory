<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $fillable = ['owner_user_id', 'name', 'is_demo', 'single_sku_enabled'];

    protected function casts(): array
    {
        return ['is_demo' => 'boolean', 'single_sku_enabled' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
