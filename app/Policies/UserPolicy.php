<?php

namespace App\Policies;

use App\Authorization\PermissionsRegistry;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsRegistry::USERS_VIEW_ANY);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(PermissionsRegistry::USERS_VIEW_ANY);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionsRegistry::USERS_CREATE);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(PermissionsRegistry::USERS_UPDATE);
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->is($user)) {
            return false;
        }

        return $user->can(PermissionsRegistry::USERS_DELETE);
    }
}
