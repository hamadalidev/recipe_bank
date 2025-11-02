<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Recipe;
use App\Repositories\Base\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RecipeRepository extends BaseRepository
{
    public function model(): string
    {
        return Recipe::class;
    }

    /**
     * Get recipes with filtering based on user role
     * 
     * @param array $request
     * @param User $user Current authenticated user
     * @return LengthAwarePaginator
     */
    public function index(\App\Models\User $user, array $request = []): LengthAwarePaginator
    {
        $columnArray = ['id', 'name', 'created_at'];
        $ascArray = ['desc', 'asc'];

        $query = $this->model->query()
            ->with(['user:id,name,email', 'cuisineType:id,name', 'attachments']);

        // Role-based filtering using policies
        if ($user->hasRole('owner')) {
            // Owner can only see their own recipes
            $query->where('user_id', $user->id);
        }
        // Admin and sub-admin can see all recipes (no filtering needed)

        // Search functionality
        if (isset($request['search']) && $request['search']) {
            $searchTerm = $request['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by cuisine type
        if (isset($request['cuisine_type_id']) && $request['cuisine_type_id']) {
            $query->where('cuisine_type_id', $request['cuisine_type_id']);
        }

        // Filter by owner (for admin/sub-admin only)
        if (isset($request['user_id']) && $request['user_id'] && $user->hasAnyRole(['admin', 'sub-admin'])) {
            $query->where('user_id', $request['user_id']);
        }

        // Sorting
        if (isset($request['column']) && isset($request['dir']) &&
            in_array($request['column'], $columnArray) &&
            in_array($request['dir'], $ascArray)) {
            $query = $query->orderBy($request['column'], $request['dir']);
        } else {
            $query = $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $pageSize = 10;
        if (isset($request['length']) && $request['length']) {
            $pageSize = (int) $request['length'];
        }

        return $query->paginate($pageSize);
    }
}