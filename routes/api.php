<?php 
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RecipeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/recipes', [RecipeController::class, 'store']);

    // We can add other v1 routes here as we build more features
});
?>