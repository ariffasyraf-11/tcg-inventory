<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class WorkspaceMembership extends Model
{
    public const ROLES = ['manager', 'inventory_operator', 'viewer'];

    protected $fillable = ['workspace_id', 'user_id', 'role'];

    protected static function booted(): void
    {
        static::saving(function (self $membership): void {
            if (! in_array($membership->role, self::ROLES, true)) {
                throw ValidationException::withMessages(['role' => 'Invalid workspace role.']);
            }

            if ($membership->workspace()->where('owner_user_id', $membership->user_id)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'The workspace owner cannot be a member.']);
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
