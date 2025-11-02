<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthControllerTest extends TestCase
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

    public function test_user_can_register_successfully(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson(route('api.v1.auth.register'), $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'permissions',
                        'created_at',
                        'updated_at'
                    ],
                    'token'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('User registered successfully', $response->json('message'));
        $this->assertEquals('John Doe', $response->json('data.user.name'));
        $this->assertEquals('john@example.com', $response->json('data.user.email'));
        $this->assertContains('owner', $response->json('data.user.roles'));
        $this->assertNotEmpty($response->json('data.token'));

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }

    public function test_registration_fails_with_invalid_data(): void
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456'
        ];

        $response = $this->postJson(route('api.v1.auth.register'), $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson(route('api.v1.auth.register'), $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);
        $user->assignRole('owner');

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson(route('api.v1.auth.login'), $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'permissions',
                        'created_at',
                        'updated_at'
                    ],
                    'token'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Login successful', $response->json('message'));
        $this->assertEquals($user->id, $response->json('data.user.id'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson(route('api.v1.auth.login'), $loginData);

        $response->assertStatus(401);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('Invalid login credentials', $response->json('message'));
    }

    public function test_login_fails_with_missing_data(): void
    {
        $response = $this->postJson(route('api.v1.auth.login'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('owner');
        
        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.auth.user'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'permissions',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($user->id, $response->json('data.user.id'));
        $this->assertEquals($user->name, $response->json('data.user.name'));
        $this->assertEquals($user->email, $response->json('data.user.email'));
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson(route('api.v1.auth.user'));

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.auth.logout'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Logout successful', $response->json('message'));
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson(route('api.v1.auth.logout'));

        $response->assertStatus(401);
    }

    public function test_user_roles_and_permissions_are_returned(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.auth.user'));

        $response->assertStatus(200);
        
        $userData = $response->json('data.user');
        $this->assertArrayHasKey('roles', $userData);
        $this->assertArrayHasKey('permissions', $userData);
        $this->assertContains('admin', $userData['roles']);
        $this->assertIsArray($userData['permissions']);
    }

}