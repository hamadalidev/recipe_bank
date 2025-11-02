<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Recipe;
use App\Models\User;
use App\Repositories\RecipeRepository;
use App\Services\Base\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class RecipeService extends BaseService
{
    public function __construct(
        private RecipeRepository $recipeRepository,
        private AttachmentService $attachmentService
    ) {
        parent::__construct($recipeRepository);
    }

    /**
     * Get paginated recipes for user based on their role
     */
    public function getRecipesForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->recipeRepository->index($user, $filters);
    }

    /**
     * Create a new recipe with attachments
     */
    public function createRecipe(array $data, User $user, ?UploadedFile $image = null): Recipe
    {
        try {
            // Set the user ID
            $data['user_id'] = $user->id;
            
            // Create the recipe
            $recipe = $this->recipeRepository->create($data);
            
            // Handle image upload if provided
            if ($image) {
                $this->attachmentService->uploadAndAttach(
                    $image,
                    $recipe,
                    'image',
                    'public',
                    'recipes'
                );
            }
            
            return $recipe->load(['user', 'cuisineType', 'attachments']);
            
        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to create recipe');
            throw $e;
        }
    }

    /**
     * Update a recipe with attachments
     */
    public function updateRecipe(Recipe $recipe, array $data, ?UploadedFile $image = null): Recipe
    {
        try {
            // Update the recipe
            $this->recipeRepository->update($recipe->id, $data);
            
            // Handle image upload if provided
            if ($image) {
                // Remove old image attachments
                $oldImages = $recipe->images();
                foreach ($oldImages as $oldImage) {
                    $this->attachmentService->delete($oldImage);
                }
                
                // Upload new image
                $this->attachmentService->uploadAndAttach(
                    $image,
                    $recipe,
                    'image',
                    'public',
                    'recipes'
                );
            }
            
            return $recipe->fresh(['user', 'cuisineType', 'attachments']);
            
        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to update recipe');
            throw $e;
        }
    }

    /**
     * Get a recipe with all its relations
     */
    public function getRecipeWithRelations(int $id): Recipe
    {
        return $this->recipeRepository->findOrFail($id, ['*'], ['user:id,name,email', 'cuisineType:id,name', 'attachments']);
    }

    /**
     * Delete a recipe and its attachments
     */
    public function deleteRecipe(Recipe $recipe): bool
    {
        try {
            // Delete all attachments first
            $attachments = $recipe->attachments;
            foreach ($attachments as $attachment) {
                $this->attachmentService->delete($attachment);
            }
            
            // Delete the recipe
            return $this->recipeRepository->delete($recipe->id);
            
        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to delete recipe');
            throw $e;
        }
    }



    /**
     * Handle common error responses
     */
    protected function handleException(\Exception $e, string $defaultMessage = 'An error occurred'): void
    {
        \Log::error($defaultMessage . ': ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);
        
        throw $e;
    }

}