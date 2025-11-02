<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Base\BaseRepository;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository
{
    public function model(): string
    {
        return User::class;
    }

    /**
     * Create a new user with encrypted password
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        
        $user = $this->create($data);
        
        // Assign default role if specified
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }
        
        return $user;
    }
}