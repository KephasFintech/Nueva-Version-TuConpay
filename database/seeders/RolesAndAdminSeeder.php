<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        foreach (UserRole::cases() as $role) {
            Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'api']);
        }

        // Create Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@tuconpay.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'), // Forzar cambio en prod
                'phone' => '+1234567890',
                'is_active' => true,
            ]
        );

        if (!$superAdmin->hasRole(UserRole::SUPER_ADMIN->value)) {
            $superAdmin->assignRole(UserRole::SUPER_ADMIN->value);
        }

        // Create a test ATC
        $atc = User::firstOrCreate(
            ['email' => 'atc@tuconpay.com'],
            [
                'name' => 'Agente Taquilla Demo',
                'password' => Hash::make('password123'),
                'phone' => '+1234567890',
                'is_active' => true,
            ]
        );

        if (!$atc->hasRole(UserRole::ATC->value)) {
            $atc->assignRole(UserRole::ATC->value);
        }
    }
}
