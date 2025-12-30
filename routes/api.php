<?php

use App\Models\Category;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\CategoryResource;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/categories', function () {
        return CategoryResource::collection(Category::all());
    });

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::delete('/auth/account', [AuthController::class, 'destroy']);
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return new \App\Http\Resources\UserResource($request->user());
    });
    Route::post('/profile', [ProfileController::class, 'update']);

    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/posts/search', [FeedController::class, 'search']);

    Route::get('/feed', [FeedController::class, 'index']);

    Route::get('/locations/{id}', [LocationController::class, 'show']);
    Route::post('/locations', [LocationController::class, 'store']);
    Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
    Route::get('/locations/{id}/posts', [LocationController::class, 'posts']);

    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    Route::post('/users/{id}/follow', [FollowController::class, 'toggle']);
    Route::post('/posts/{id}/like', [LikeController::class, 'togglePostLike']);

    Route::get('/posts/{id}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{id}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/users/{username}', [UserController::class, 'show']);

    Route::get('/users/{id}/posts', [UserController::class, 'posts']);
    Route::get('/users/{id}/followers', [UserController::class, 'followers']);
    Route::get('/users/{id}/following', [UserController::class, 'following']);
    Route::get('/posts/{id}/likes', [LikeController::class, 'index']);
});
