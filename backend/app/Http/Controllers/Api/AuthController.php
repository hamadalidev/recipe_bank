<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Base\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Authentication
 *
 * APIs for user authentication and management
 */
class AuthController extends BaseController
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    /**
     * Register a new user
     *
     * Register a new user with owner role by default.
     *
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password (minimum 8 characters). Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Example: password123
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "message": "User registered successfully",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "roles": ["owner"]
     *     },
     *     "token": "1|abc123...xyz"
     *   }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userRepository->createUser([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'owner', // Assign owner role by default
        ]);

        $user->load(['roles', 'permissions']);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    /**
     * Login user
     *
     * Authenticate user and return access token.
     *
     * @bodyParam email string required The user's email address. Example: admin@recipe-book.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "Login successful",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Admin User",
     *       "email": "admin@recipe-book.com",
     *       "roles": ["admin"],
     *       "permissions": ["view-recipes", "create-recipe", "..."]
     *     },
     *     "token": "1|abc123...xyz"
     *   }
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Invalid login credentials', 401);
        }

        $user = Auth::user();
        $user->load(['roles', 'permissions']);
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Get authenticated user
     *
     * Get the currently authenticated user's information.
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "Success",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Admin User",
     *       "email": "admin@recipe-book.com",
     *       "roles": ["admin"],
     *       "permissions": ["view-recipes", "create-recipe", "..."]
     *     }
     *   }
     * }
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['roles', 'permissions']);

        return $this->successResponse([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Logout user
     *
     * Logout the authenticated user and revoke access token.
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "Logout successful",
     *   "data": null
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout successful');
    }
}
