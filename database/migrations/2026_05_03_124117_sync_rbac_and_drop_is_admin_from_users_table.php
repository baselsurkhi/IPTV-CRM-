<?php

use App\Authorization\PermissionsRegistry;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionsRegistry::all() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (PermissionsRegistry::roles() as $roleName => $permissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissionNames);
        }

        if (! Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        foreach (User::query()->where('is_admin', true)->cursor() as $user) {
            if (! $user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
            }
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        foreach (Role::query()->cursor() as $role) {
            $role->delete();
        }

        foreach (Permission::query()->cursor() as $permission) {
            $permission->delete();
        }
    }
};
