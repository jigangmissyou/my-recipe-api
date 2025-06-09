<?php
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\RecipeController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 认证相关路由
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [GoogleAuthController::class, 'googleLogin']); // Google 登录/注册

    // 需要认证的路由
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/recipes', [RecipeController::class, 'store']);
        Route::post('/auth/google/link', [GoogleAuthController::class, 'linkGoogleAccount']); // 关联 Google 账号
        // Get user profile
        Route::get('/user/profile', [UserController::class, 'profile']);
        // Update user profile
        Route::put('/user/profile', [UserController::class, 'updateProfile']);
        // upload recipe image
        Route::post('/recipes/{recipe}/image', [RecipeController::class, 'uploadImage']);
        // upload temporary image
        Route::post('/upload', [RecipeController::class, 'uploadTemporaryImage']);
        Route::get('/recipes/my', [RecipeController::class, 'myRecipes']);
        Route::post('/recipes/{recipe}/comments', [RecipeController::class, 'addComment']);
        Route::put('/recipes/{recipe}/comments/{commentId}', [RecipeController::class, 'updateComment']);
        Route::delete('/recipes/{recipe}/comments/{commentId}', [RecipeController::class, 'deleteComment']);
        Route::get('/my-comments', [RecipeController::class, 'myComments']);
        Route::post('/recipes/{recipe}/favorite', [RecipeController::class, 'toggleFavorite']);
        Route::get('/recipes/favorites', [RecipeController::class, 'favoriteRecipes']);
    });

    // Public routes with optional authentication
    Route::get('/recipes', [RecipeController::class, 'index']);
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->middleware('optional.auth');
    Route::get('/recipes/{recipe}/comments', [RecipeController::class, 'getComments']);
    Route::get('/recipe-tags', [RecipeController::class, 'getTags']);

});