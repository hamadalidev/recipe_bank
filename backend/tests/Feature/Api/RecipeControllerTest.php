<?php

namespace Tests\Feature\Api;

use App\Models\CuisineType;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        Permission::create(['name' => 'list-recipes']);
        Permission::create(['name' => 'add-recipe']);
        Permission::create(['name' => 'edit-recipe']);
        Permission::create(['name' => 'delete-recipe']);
        Permission::create(['name' => 'manage-users']);
        Permission::create(['name' => 'manage-cuisine-types']);
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $subAdminRole = Role::create(['name' => 'sub-admin']);
        $ownerRole = Role::create(['name' => 'owner']);
        
        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());
        $subAdminRole->givePermissionTo(['list-recipes']);
        $ownerRole->givePermissionTo(['list-recipes', 'add-recipe']);
        
        Storage::fake('public');
    }

    public function test_admin_can_get_all_recipes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        
        $cuisineType = CuisineType::factory()->create();
        
        // Create recipes by different users
        Recipe::factory(3)->create(['user_id' => $admin->id, 'cuisine_type_id' => $cuisineType->id]);
        Recipe::factory(2)->create(['user_id' => $owner->id, 'cuisine_type_id' => $cuisineType->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(route('api.v1.recipes.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'list' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'ingredients',
                            'steps',
                            'image',
                            'created_at',
                            'updated_at',
                            'user' => ['id', 'name', 'email'],
                            'cuisine_type' => ['id', 'name'],
                            'attachments'
                        ]
                    ],
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(5, $response->json('data.pagination.total'));
    }

    public function test_owner_can_only_see_own_recipes(): void
    {
        $owner1 = User::factory()->create();
        $owner1->assignRole('owner');
        
        $owner2 = User::factory()->create();
        $owner2->assignRole('owner');
        
        $cuisineType = CuisineType::factory()->create();
        
        // Create recipes by different owners
        Recipe::factory(3)->create(['user_id' => $owner1->id, 'cuisine_type_id' => $cuisineType->id]);
        Recipe::factory(2)->create(['user_id' => $owner2->id, 'cuisine_type_id' => $cuisineType->id]);

        Sanctum::actingAs($owner1);

        $response = $this->getJson(route('api.v1.recipes.index'));

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.pagination.total'));
        
        // Verify all returned recipes belong to owner1
        foreach ($response->json('data.list') as $recipe) {
            $this->assertEquals($owner1->id, $recipe['user']['id']);
        }
    }

    public function test_unauthenticated_user_cannot_access_recipes(): void
    {
        $response = $this->getJson(route('api.v1.recipes.index'));

        $response->assertStatus(401);
    }

    public function test_user_without_permissions_cannot_access_recipes(): void
    {
        $user = User::factory()->create();
        // No role assigned, so no permissions
        
        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.recipes.index'));

        $response->assertStatus(403);
    }

    public function test_can_search_recipes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $cuisineType = CuisineType::factory()->create();

        Recipe::factory()->create([
            'name' => 'Delicious Pasta',
            'user_id' => $user->id,
            'cuisine_type_id' => $cuisineType->id
        ]);
        
        Recipe::factory()->create([
            'name' => 'Amazing Pizza',
            'user_id' => $user->id,
            'cuisine_type_id' => $cuisineType->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.recipes.index', ['search' => 'Pasta']));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.pagination.total'));
        $this->assertStringContainsString('Pasta', $response->json('data.list.0.name'));
    }

    public function test_can_filter_recipes_by_cuisine_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $italianCuisine = CuisineType::factory()->create(['name' => 'Italian']);
        $chineseCuisine = CuisineType::factory()->create(['name' => 'Chinese']);

        Recipe::factory(3)->create(['user_id' => $user->id, 'cuisine_type_id' => $italianCuisine->id]);
        Recipe::factory(2)->create(['user_id' => $user->id, 'cuisine_type_id' => $chineseCuisine->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.recipes.index', ['cuisine_type_id' => $italianCuisine->id]));

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.pagination.total'));
    }

    public function test_owner_can_create_recipe(): void
    {
        $user = User::factory()->create();
        $user->assignRole('owner');
        $cuisineType = CuisineType::factory()->create();

        $recipeData = [
            'name' => 'Test Recipe',
            'description' => 'A test recipe description',
            'ingredients' => ['Ingredient 1', 'Ingredient 2'],
            'steps' => ['Step 1', 'Step 2'],
            'cuisine_type_id' => $cuisineType->id
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.recipes.store'), $recipeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'ingredients',
                    'steps',
                    'user',
                    'cuisine_type',
                    'attachments'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Recipe created successfully', $response->json('message'));
        $this->assertEquals('Test Recipe', $response->json('data.name'));
        $this->assertEquals($user->id, $response->json('data.user.id'));

        $this->assertDatabaseHas('recipes', [
            'name' => 'Test Recipe',
            'user_id' => $user->id,
            'cuisine_type_id' => $cuisineType->id
        ]);
    }

    public function test_can_create_recipe_with_image(): void
    {
        $user = User::factory()->create();
        $user->assignRole('owner');
        $cuisineType = CuisineType::factory()->create();

        $image = UploadedFile::fake()->image('recipe.jpg', 800, 600);

        $recipeData = [
            'name' => 'Recipe with Image',
            'description' => 'A recipe with an image',
            'ingredients' => ['Ingredient 1'],
            'steps' => ['Step 1'],
            'cuisine_type_id' => $cuisineType->id,
            'image' => $image
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.recipes.store'), $recipeData);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
        
        // Verify image attachment was created
        $recipe = Recipe::find($response->json('data.id'));
        $this->assertCount(1, $recipe->attachments);
        $this->assertTrue($recipe->attachments->first()->isImage());
    }

    public function test_recipe_creation_validation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('owner');

        $invalidData = [
            'name' => '', // Required
            'description' => '', // Required
            'ingredients' => [], // Required and min:1
            'steps' => [], // Required and min:1
            'cuisine_type_id' => 999 // Non-existent
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.recipes.store'), $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'ingredients', 'steps', 'cuisine_type_id']);
    }

    public function test_user_without_create_permission_cannot_create_recipe(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sub-admin'); // Only has list permission
        $cuisineType = CuisineType::factory()->create();

        $recipeData = [
            'name' => 'Test Recipe',
            'description' => 'A test recipe',
            'ingredients' => ['Ingredient 1'],
            'steps' => ['Step 1'],
            'cuisine_type_id' => $cuisineType->id
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.recipes.store'), $recipeData);

        $response->assertStatus(403);
    }

    public function test_can_view_single_recipe(): void
    {
        $user = User::factory()->create();
        $user->assignRole('owner');
        $cuisineType = CuisineType::factory()->create();
        
        $recipe = Recipe::factory()->create([
            'user_id' => $user->id,
            'cuisine_type_id' => $cuisineType->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.recipes.show', $recipe));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'ingredients',
                    'steps',
                    'user',
                    'cuisine_type',
                    'attachments'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($recipe->id, $response->json('data.id'));
    }

    public function test_admin_can_update_any_recipe(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        
        $cuisineType = CuisineType::factory()->create();
        
        $recipe = Recipe::factory()->create([
            'user_id' => $owner->id,
            'cuisine_type_id' => $cuisineType->id,
            'name' => 'Original Name'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'ingredients' => ['Updated ingredient'],
            'steps' => ['Updated step'],
            'cuisine_type_id' => $cuisineType->id
        ];

        Sanctum::actingAs($admin);

        $response = $this->putJson(route('api.v1.recipes.update', $recipe), $updateData);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Updated Name', $response->json('data.name'));

        $this->assertDatabaseHas('recipes', [
            'id' => $recipe->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_owner_cannot_update_recipe_without_permission(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner'); // Only has list and add permissions
        
        $cuisineType = CuisineType::factory()->create();
        
        $recipe = Recipe::factory()->create([
            'user_id' => $owner->id,
            'cuisine_type_id' => $cuisineType->id
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'ingredients' => ['Updated ingredient'],
            'steps' => ['Updated step'],
            'cuisine_type_id' => $cuisineType->id
        ];

        Sanctum::actingAs($owner);

        $response = $this->putJson(route('api.v1.recipes.update', $recipe), $updateData);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_any_recipe(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        
        $cuisineType = CuisineType::factory()->create();
        
        $recipe = Recipe::factory()->create([
            'user_id' => $owner->id,
            'cuisine_type_id' => $cuisineType->id
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson(route('api.v1.recipes.destroy', $recipe));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Recipe deleted successfully', $response->json('message'));

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_owner_cannot_delete_recipe_without_permission(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner'); // Only has list and add permissions
        
        $cuisineType = CuisineType::factory()->create();
        
        $recipe = Recipe::factory()->create([
            'user_id' => $owner->id,
            'cuisine_type_id' => $cuisineType->id
        ]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson(route('api.v1.recipes.destroy', $recipe));

        $response->assertStatus(403);
    }

    public function test_can_paginate_recipes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $cuisineType = CuisineType::factory()->create();

        Recipe::factory(25)->create([
            'user_id' => $user->id,
            'cuisine_type_id' => $cuisineType->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.recipes.index', ['length' => 5]));

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.pagination.count'));
        $this->assertEquals(25, $response->json('data.pagination.total'));
        $this->assertEquals(5, $response->json('data.pagination.total_pages'));
    }

    public function test_recipe_attachments_are_deleted_when_recipe_is_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $cuisineType = CuisineType::factory()->create();
        
        $recipe = Recipe::factory()->create([
            'user_id' => $admin->id,
            'cuisine_type_id' => $cuisineType->id
        ]);

        // Create an attachment for the recipe
        $recipe->attachments()->create([
            'original_name' => 'test.jpg',
            'file_name' => 'test_123.jpg',
            'file_path' => 'recipes/test_123.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'disk' => 'public',
            'type' => 'image'
        ]);

        $attachmentId = $recipe->attachments->first()->id;

        Sanctum::actingAs($admin);

        $response = $this->deleteJson(route('api.v1.recipes.destroy', $recipe));

        $response->assertStatus(200);
        
        // Verify recipe and attachments are deleted
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachmentId]);
    }
}