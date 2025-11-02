<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Base\BaseController;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Http\Resources\RecipeCollection;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\RecipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Recipes
 *
 * APIs for managing recipes with role-based access control
 */
class RecipeController extends BaseController
{
    public function __construct(
        private RecipeService $recipeService
    ) {}

    /**
     * List recipes
     *
     * Get a paginated list of recipes with role-based filtering.
     * - Owner: sees only own recipes
     * - Sub-admin: sees all recipes (read-only)
     * - Admin: sees all recipes (full access)
     *
     * @queryParam search string Search term to filter recipes by name or description. Example: pasta
     * @queryParam cuisine_type_id int Filter by cuisine type ID. Example: 1
     * @queryParam user_id int Filter by user ID (admin/sub-admin only). Example: 2
     * @queryParam column string Field to sort by. Must be one of: id, name, created_at. Example: name
     * @queryParam dir string Sort direction. Must be one of: asc, desc. Example: asc
     * @queryParam length int Number of items per page (default: 10). Example: 15
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "Success",
     *   "data": {
     *     "list": [
     *       {
     *         "id": 1,
     *         "name": "Spaghetti Carbonara",
     *         "description": "Classic Italian pasta dish",
     *         "ingredients": ["spaghetti", "eggs", "bacon", "parmesan"],
     *         "steps": ["Cook pasta", "Mix eggs and cheese", "Combine"],
     *         "image": null,
     *         "created_at": "2023-01-01 12:00:00",
     *         "user": {
     *           "id": 1,
     *           "name": "John Doe",
     *           "email": "john@example.com"
     *         },
     *         "cuisine_type": {
     *           "id": 1,
     *           "name": "Italian"
     *         },
     *         "can_edit": true,
     *         "can_delete": true
     *       }
     *     ],
     *     "pagination": {
     *       "total": 25,
     *       "count": 10,
     *       "per_page": 10,
     *       "current_page": 1,
     *       "total_pages": 3
     *     }
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Recipe::class);
        
        $user = $request->user();
        $requestData = $request->only(['search', 'cuisine_type_id', 'user_id', 'column', 'dir', 'length']);
        
        $recipes = $this->recipeService->getRecipesForUser($user, $requestData);

        return $this->successResponse(
            new RecipeCollection($recipes)
        );
    }

    /**
     * Create recipe
     *
     * Create a new recipe. Available to owners and admins.
     *
     * @bodyParam name string required The recipe name. Example: Chocolate Cake
     * @bodyParam description string required Recipe description. Example: Delicious chocolate cake recipe
     * @bodyParam ingredients array required Array of ingredients. Example: ["flour", "sugar", "chocolate"]
     * @bodyParam steps array required Array of cooking steps. Example: ["Mix ingredients", "Bake for 30 min"]
     * @bodyParam cuisine_type_id int required Cuisine type ID. Example: 1
     * @bodyParam image file Optional recipe image.
     *
     * @authenticated
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "message": "Recipe created successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Chocolate Cake",
     *     "description": "Delicious chocolate cake recipe",
     *     "ingredients": ["flour", "sugar", "chocolate"],
     *     "steps": ["Mix ingredients", "Bake for 30 min"],
     *     "image": null,
     *     "created_at": "2023-01-01 12:00:00",
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "cuisine_type": {
     *       "id": 1,
     *       "name": "Italian"
     *     },
     *     "can_edit": true,
     *     "can_delete": true
     *   }
     * }
     */
    public function store(StoreRecipeRequest $request): JsonResponse
    {
        $this->authorize('create', Recipe::class);
        
        $user = $request->user();
        $data = $request->validated();
        $image = $request->hasFile('image') ? $request->file('image') : null;

        $recipe = $this->recipeService->createRecipe($data, $user, $image);

        return $this->successResponse(
            new RecipeResource($recipe),
            'Recipe created successfully',
            201
        );
    }

    /**
     * Show recipe
     *
     * Get a specific recipe by ID. Access controlled by role and ownership.
     *
     * @urlParam id int required Recipe ID. Example: 1
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "Success",
     *   "data": {
     *     "id": 1,
     *     "name": "Spaghetti Carbonara",
     *     "description": "Classic Italian pasta dish",
     *     "ingredients": ["spaghetti", "eggs", "bacon", "parmesan"],
     *     "steps": ["Cook pasta", "Mix eggs and cheese", "Combine"],
     *     "image": null,
     *     "created_at": "2023-01-01 12:00:00",
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "cuisine_type": {
     *       "id": 1,
     *       "name": "Italian"
     *     },
     *     "can_edit": true,
     *     "can_delete": true
     *   }
     * }
     */
    public function show(Request $request, Recipe $recipe): JsonResponse
    {
        $this->authorize('view', $recipe);

        $recipe = $this->recipeService->getRecipeWithRelations($recipe->id);

        return $this->successResponse(new RecipeResource($recipe));
    }

    /**
     * Update recipe
     *
     * Update a recipe. Only recipe owner or admin can update.
     *
     * @urlParam id int required Recipe ID. Example: 1
     * @bodyParam name string The recipe name. Example: Updated Chocolate Cake
     * @bodyParam description string Recipe description. Example: Updated description
     * @bodyParam ingredients array Array of ingredients. Example: ["flour", "sugar", "chocolate", "vanilla"]
     * @bodyParam steps array Array of cooking steps. Example: ["Mix dry ingredients", "Add wet ingredients", "Bake for 35 min"]
     * @bodyParam cuisine_type_id int Cuisine type ID. Example: 2
     * @bodyParam image file Optional recipe image.
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "Recipe updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Updated Chocolate Cake",
     *     "description": "Updated description",
     *     "ingredients": ["flour", "sugar", "chocolate", "vanilla"],
     *     "steps": ["Mix dry ingredients", "Add wet ingredients", "Bake for 35 min"],
     *     "image": "recipes/updated-image.jpg",
     *     "created_at": "2023-01-01 12:00:00",
     *     "updated_at": "2023-01-02 12:00:00",
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "cuisine_type": {
     *       "id": 2,
     *       "name": "French"
     *     },
     *     "can_edit": true,
     *     "can_delete": true
     *   }
     * }
     */
    public function update(UpdateRecipeRequest $request, Recipe $recipe): JsonResponse
    {
        $this->authorize('update', $recipe);

        $data = $request->validated();
        $image = $request->hasFile('image') ? $request->file('image') : null;

        $recipe = $this->recipeService->updateRecipe($recipe, $data, $image);

        return $this->successResponse(
            new RecipeResource($recipe),
            'Recipe updated successfully'
        );
    }

    /**
     * Delete recipe
     *
     * Delete a recipe. Only recipe owner or admin can delete.
     *
     * @urlParam id int required Recipe ID. Example: 1
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "Recipe deleted successfully",
     *   "data": null
     * }
     */
    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        $this->authorize('delete', $recipe);

        $this->recipeService->deleteRecipe($recipe);

        return $this->successResponse(null, 'Recipe deleted successfully');
    }
}
