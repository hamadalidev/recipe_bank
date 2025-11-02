<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecipePolicy
{
    /**
     * Determine whether the user can view any recipes.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-recipes');
    }

    /**
     * Determine whether the user can view the recipe.
     */
    public function view(User $user, Recipe $recipe): bool
    {
        return $user->hasPermissionTo('list-recipes');
    }

    /**
     * Determine whether the user can create recipes.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('add-recipe');
    }

    /**
     * Determine whether the user can update the recipe.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        return $user->hasPermissionTo('edit-recipe');
    }

    /**
     * Determine whether the user can delete the recipe.
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        return $user->hasPermissionTo('delete-recipe');
    }

    /**
     * Determine whether the user can view all recipes (admin/sub-admin only).
     */
    public function viewAll(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'sub-admin']);
    }
}