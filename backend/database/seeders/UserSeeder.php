<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = config('constants.permissions');

        foreach ($roles as $roleName => $permissions) {
            $this->createUserForRole($roleName);
        }

        $this->command->info('Users seeded successfully for all roles!');
    }

    /**
     * Create a user for the specified role
     */
    private function createUserForRole(string $roleName): void
    {
        // Check if role exists
        $role = Role::where('name', $roleName)->first();
        
        if (!$role) {
            $this->command->warn("Role '{$roleName}' not found. Skipping user creation.");
            return;
        }

        // Create user data
        $userData = [
            'name' => ucfirst($roleName) . ' User',
            'email' => $roleName . '@recipebank.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ];

        // Create or update user
        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            $userData
        );

        // Assign role to user
        $user->syncRoles([$roleName]);

        $this->command->info("User '{$userData['name']}' created with role '{$roleName}' (Email: {$userData['email']})");
    }
}