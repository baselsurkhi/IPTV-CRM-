<?php

namespace App\Policies;

use App\Authorization\PermissionsRegistry;
use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsRegistry::DEVICES_VIEW_ANY);
    }

    public function view(User $user, Device $device): bool
    {
        return $user->can(PermissionsRegistry::DEVICES_VIEW_ANY);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionsRegistry::DEVICES_CREATE);
    }

    public function update(User $user, Device $device): bool
    {
        return $user->can(PermissionsRegistry::DEVICES_UPDATE);
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->can(PermissionsRegistry::DEVICES_DELETE);
    }
}
