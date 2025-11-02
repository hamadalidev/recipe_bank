<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CuisineTypeController;
use App\Http\Controllers\Api\RecipeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    
    // Authentication routes (public)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        
        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('user', [AuthController::class, 'user'])->name('user');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    // Cuisine types dropdown (only endpoint used by frontend)
    Route::get('cuisine-types/dropdown', [CuisineTypeController::class, 'dropdown'])->name('cuisine-types.dropdown');

    // Protected routes requiring authentication
    Route::middleware('auth:sanctum')->group(function () {
        
        // Recipe management routes (only endpoints used by frontend)
        Route::prefix('recipes')->name('recipes.')->group(function () {
            Route::get('/', [RecipeController::class, 'index'])->name('index');
            Route::post('/', [RecipeController::class, 'store'])->name('store');
            Route::get('{recipe}', [RecipeController::class, 'show'])->name('show');
            Route::put('{recipe}', [RecipeController::class, 'update'])->name('update');
            Route::delete('{recipe}', [RecipeController::class, 'destroy'])->name('destroy');
        });
    });

});
