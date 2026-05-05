<?php

namespace App\Policies;

use App\Authorization\PermissionsRegistry;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsRegistry::ROLES_MANAGE);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(PermissionsRegistry::ROLES_MANAGE);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionsRegistry::ROLES_MANAGE);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(PermissionsRegistry::ROLES_MANAGE);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(PermissionsRegistry::ROLES_MANAGE);
    }
}
