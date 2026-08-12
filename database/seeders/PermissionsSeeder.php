<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create all permissions
        foreach (PermissionEnum::values() as $permissionValue) {
            Permission::firstOrCreate(['name' => $permissionValue, 'guard_name' => 'api']);
        }

        // 2. Define permission mapping
        $rolePermissions = [
            UserRole::SUPER_ADMIN->value => PermissionEnum::values(), // Super Admin gets all permissions

            UserRole::ATC->value => [
                PermissionEnum::CLIENT_CREATE->value,
                PermissionEnum::TICKET_CREATE->value,
                PermissionEnum::TICKET_SEND_TO_CLIENT->value,
                PermissionEnum::TICKET_UPLOAD_AFFIDAVIT->value,
                PermissionEnum::TICKET_UPLOAD_PROOF->value,
                PermissionEnum::TICKET_CONFIRM_DELIVERY->value,
                PermissionEnum::CLIENT_VIEW->value,
            ],

            UserRole::ADMIN->value => [
                PermissionEnum::TICKET_ASSIGN_RATE_INTERNAL->value,
                PermissionEnum::TICKET_VERIFY_PAYMENT_INTERNAL->value,
                PermissionEnum::TICKET_PROCESS_BRIDGE->value,
                PermissionEnum::TICKET_DISPATCH_COURIER->value,
                PermissionEnum::TICKET_CONFIRM_DELIVERY->value,
                PermissionEnum::CASH_CLOSE_SHIFT->value,
            ],

            UserRole::DIRECTION->value => [
                PermissionEnum::TICKET_ASSIGN_RATE_EXTERNAL->value,
                PermissionEnum::TICKET_VERIFY_PAYMENT_EXTERNAL->value,
                PermissionEnum::TICKET_PROCESS_BRIDGE->value,
                PermissionEnum::TICKET_CONFIRM_DELIVERY->value,
                PermissionEnum::USER_MANAGE->value,
            ],

            UserRole::DATA_ANALYST->value => [
                PermissionEnum::TICKET_AUDIT_AGENTS->value,
                PermissionEnum::TICKET_SETTLE->value,
                PermissionEnum::CASH_CLOSE_SHIFT->value,
            ],

            UserRole::COURIER->value => [
                // El motorizado no tiene vista en el sistema por ahora, la admin se encarga
            ],

            UserRole::CLIENT->value => [
                PermissionEnum::TICKET_UPLOAD_AFFIDAVIT->value,
                PermissionEnum::TICKET_UPLOAD_PROOF->value,
            ],

            // Broker, External Admin, and Provider primarily have read access 
            // handled by Policies, so no specific operative permissions are assigned here.
            UserRole::BROKER->value         => [],
            UserRole::EXTERNAL_ADMIN->value => [],
            UserRole::PROVIDER->value       => [],
        ];

        // 3. Create roles and assign permissions
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);

            if (!empty($permissions)) {
                $role->syncPermissions($permissions);
            }
        }

        // 4. Create Super Admin User (if not exists)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@tuconpay.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'phone' => '+1234567890',
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole(UserRole::SUPER_ADMIN->value);
    }
}
