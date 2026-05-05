<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'مدير النظام',
                'password' => 'password',
                'locale' => 'ar',
            ],
        );

        $superAdminRole = Role::query()->where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdminRole !== null && ! $admin->hasRole('super-admin')) {
            $admin->assignRole($superAdminRole);
        }
    }
}
