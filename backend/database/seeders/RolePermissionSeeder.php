<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionsConfig = config('constants.permissions');

        // Create all permissions first
        $this->createPermissions($permissionsConfig);

        // Create roles and assign permissions
        $this->createRolesAndAssignPermissions($permissionsConfig);

        $this->command->info('Roles and permissions seeded successfully!');
    }

    /**
     * Create all permissions from configuration
     */
    private function createPermissions(array $permissionsConfig): void
    {
        $allPermissions = collect($permissionsConfig)
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('Created ' . count($allPermissions) . ' permissions.');
    }

    /**
     * Create roles and assign their permissions
     */
    private function createRolesAndAssignPermissions(array $permissionsConfig): void
    {
        foreach ($permissionsConfig as $roleName => $permissions) {
            // Create or get the role
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);

            // Clear existing permissions for this role
            $role->permissions()->detach();

            // Assign new permissions
            foreach ($permissions as $permission) {
                $permissionModel = Permission::where('name', $permission)->first();
                if ($permissionModel) {
                    $role->givePermissionTo($permissionModel);
                }
            }

            $this->command->info("Role '{$roleName}' created with " . count($permissions) . ' permissions.');
        }
    }
}
