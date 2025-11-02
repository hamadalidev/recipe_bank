<?php

namespace Tests\Feature\Api;

use App\Models\CuisineType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CuisineTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles for testing
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'sub-admin']);
        Role::create(['name' => 'owner']);
    }

    public function test_can_get_cuisine_types_dropdown(): void
    {
        // Create test cuisine types with specific names
        $italian = CuisineType::factory()->create(['name' => 'Italian', 'description' => 'Italian cuisine']);
        $chinese = CuisineType::factory()->create(['name' => 'Chinese', 'description' => 'Chinese cuisine']);
        $mexican = CuisineType::factory()->create(['name' => 'Mexican', 'description' => 'Mexican cuisine']);

        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message', 
                'data' => [
                    '*' => [
                        'id',
                        'name'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));
        
        // Verify the structure and that all created cuisine types are present
        $cuisineTypes = $response->json('data');
        $cuisineNames = collect($cuisineTypes)->pluck('name')->toArray();
        
        $this->assertContains('Italian', $cuisineNames);
        $this->assertContains('Chinese', $cuisineNames);
        $this->assertContains('Mexican', $cuisineNames);
    }

    public function test_cuisine_types_dropdown_returns_empty_when_no_data(): void
    {
        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEmpty($response->json('data'));
    }

    public function test_cuisine_types_dropdown_only_returns_id_and_name(): void
    {
        CuisineType::factory()->create([
            'name' => 'Italian',
            'description' => 'Italian cuisine with pasta and pizza',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));

        $response->assertStatus(200);
        
        $cuisineType = $response->json('data.0');
        $this->assertArrayHasKey('id', $cuisineType);
        $this->assertArrayHasKey('name', $cuisineType);
        $this->assertArrayNotHasKey('description', $cuisineType);
        $this->assertArrayNotHasKey('created_at', $cuisineType);
        $this->assertArrayNotHasKey('updated_at', $cuisineType);
    }

    public function test_cuisine_types_are_ordered_by_name(): void
    {
        // Create cuisine types in random order
        CuisineType::factory()->create(['name' => 'Zebra Cuisine']);
        CuisineType::factory()->create(['name' => 'Alpha Cuisine']);
        CuisineType::factory()->create(['name' => 'Beta Cuisine']);

        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));

        $response->assertStatus(200);
        
        $cuisineTypes = $response->json('data');
        $cuisineNames = collect($cuisineTypes)->pluck('name')->toArray();
        
        // Verify they are in alphabetical order
        $this->assertEquals('Alpha Cuisine', $cuisineNames[0]);
        $this->assertEquals('Beta Cuisine', $cuisineNames[1]);
        $this->assertEquals('Zebra Cuisine', $cuisineNames[2]);
    }

    public function test_cuisine_types_dropdown_works_without_authentication(): void
    {
        // This endpoint should be publicly accessible
        CuisineType::factory()->create(['name' => 'Italian']);

        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_cuisine_types_dropdown_handles_large_dataset(): void
    {
        // Create many cuisine types
        CuisineType::factory(50)->create();

        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertCount(50, $response->json('data'));
    }

    public function test_cuisine_types_dropdown_handles_special_characters(): void
    {
        CuisineType::factory()->create(['name' => 'Café Français']);
        CuisineType::factory()->create(['name' => 'Español Tapas']);
        CuisineType::factory()->create(['name' => '中国菜']);

        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));
        
        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Café Français', $names);
        $this->assertContains('Español Tapas', $names);
        $this->assertContains('中国菜', $names);
    }

    public function test_cuisine_types_response_format_is_consistent(): void
    {
        CuisineType::factory()->create(['name' => 'Test Cuisine']);

        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name'
                    ]
                ]
            ]);

        // Verify response format matches expected API standard
        $this->assertTrue($response->json('success'));
        $this->assertIsString($response->json('message'));
        $this->assertIsArray($response->json('data'));
        
        // Verify each cuisine type has correct structure
        foreach ($response->json('data') as $cuisineType) {
            $this->assertIsInt($cuisineType['id']);
            $this->assertIsString($cuisineType['name']);
        }
    }

    public function test_cuisine_types_dropdown_performance(): void
    {
        // Create a substantial number of cuisine types
        CuisineType::factory(100)->create();

        $startTime = microtime(true);
        $response = $this->getJson(route('api.v1.cuisine-types.dropdown'));
        $endTime = microtime(true);

        $response->assertStatus(200);
        
        // Response should be fast (less than 1 second)
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $executionTime, 'Cuisine types dropdown should respond quickly');
    }
}