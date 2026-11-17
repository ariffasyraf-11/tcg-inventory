<?php

namespace App\Support;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

class WorkspaceAccess
{
    public function role(User $user, Workspace $workspace): ?string
    {
        if ($workspace->owner_user_id === $user->id) {
            return 'owner';
        }

        if ($workspace->is_demo) {
            return null;
        }

        return WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->value('role');
    }

    public function allows(User $user, Workspace $workspace, string $ability): bool
    {
        $role = $this->role($user, $workspace);

        return match ($ability) {
            'view' => in_array($role, ['owner', 'manager', 'inventory_operator', 'viewer'], true),
            'stock' => in_array($role, ['owner', 'manager', 'inventory_operator'], true),
            'catalog', 'sales' => in_array($role, ['owner', 'manager'], true),
            'manage' => $role === 'owner',
            default => false,
        };
    }
}
