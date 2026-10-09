<?php

use Illuminate\Support\Facades\Route;
use Modules\Community\Http\Controllers\User\PostController;
use Modules\Community\Http\Controllers\User\ReelController;
use Modules\Community\Http\Controllers\User\StoryController;
use Modules\Community\Http\Controllers\User\CommentController;
use Modules\Community\Http\Controllers\User\ReactionController;
use Modules\Community\Http\Controllers\User\PostReportController;

// Public community routes (no auth)
Route::controller(PostController::class)->prefix('posts')->group(function ($route) {
    $route->get('index', 'index');
    $route->get('featured', 'featured');
    $route->get('search', 'search');
    $route->get('show/{id}', 'show');
    $route->get('{id}/comments', 'comments');
    $route->get('{id}/top-level-comments', 'topLevelComments');
    $route->get('user/{userId}/posts', 'userPosts');
});
Route::controller(ReelController::class)->prefix('reels')->group(function ($route) {
    $route->get('index', 'index');
    $route->get('featured', 'featured');
    $route->get('search', 'search');
    $route->get('show/{id}', 'show');
    $route->get('{id}/comments', 'comments');
    $route->get('{id}/top-level-comments', 'topLevelComments');
    $route->get('user/{userId}/reels', 'userReels');
});
Route::controller(StoryController::class)->prefix('stories')->group(function ($route) {
    $route->get('index', 'index');
    $route->get('featured', 'featured');
    $route->get('most-viewed', 'mostViewed');
    $route->get('search', 'search');
    $route->get('show/{id}', 'show');
    $route->get('{id}/replies', 'replies');
    $route->get('{id}/top-level-replies', 'topLevelReplies');
    $route->get('user/{userId}/stories', 'userStories');
});

// Authenticated community routes
Route::middleware(['auth:api', 'validate.device', 'profile.complete'])->group(function () {
    Route::controller(PostController::class)->prefix('posts')->group(function ($route) {
        $route->get('all', 'allPosts');
        $route->post('', 'store');
        $route->post('{id}', 'update');
        $route->delete('{id}', 'destroy');
        $route->get('my-posts', 'myPosts');
        $route->get('user/posts', 'userPosts');
        $route->post('{id}/toggle-reaction', 'toggleReaction');
        $route->get('{id}/reactions', 'reactions');
        $route->post('{id}/comments', 'addComment');
    });
    Route::controller(ReelController::class)->prefix('reels')->group(function ($route) {
        $route->post('', 'store');
        $route->post('{id}', 'update');
        $route->delete('{id}', 'destroy');
        $route->get('my-reels', 'myReels');
        $route->post('{id}/toggle-reaction', 'toggleReaction');
        $route->get('{id}/reactions', 'reactions');
        $route->post('{id}/comments', 'addComment');
    });
    Route::controller(StoryController::class)->prefix('stories')->group(function ($route) {
        $route->get('all', 'allStories');
        $route->post('', 'store');
        $route->delete('{id}', 'destroy');
        $route->get('my-stories', 'myStories');
        $route->get('user/stories', 'userStories');
        $route->post('{id}/toggle-reaction', 'toggleReaction');
        $route->get('{id}/reactions', 'reactions');
        $route->post('{id}/replies', 'addReply');
        $route->get('{id}/viewers', 'viewers');
        $route->get('view/{id}', 'view');
    });
    Route::controller(CommentController::class)->prefix('comments')->group(function ($route) {
        $route->get('', 'index');
        $route->get('my-comments', 'myComments');
        $route->get('user/{userId}/comments', 'userComments');
        $route->get('post/{postId}/comments', 'postComments');
        $route->get('reel/{reelId}/comments', 'reelComments');
        $route->get('{id}', 'show');
        $route->post('{id}', 'update');
        $route->delete('{id}', 'destroy');
        $route->get('{id}/replies', 'replies');
        $route->post('{id}/replies', 'addReply');
        $route->get('search', 'search');
    });
    Route::controller(ReactionController::class)->prefix('reactions')->group(function ($route) {
        $route->get('', 'index');
        $route->get('my-reactions', 'myReactions');
        $route->get('user/{userId}/reactions', 'userReactions');
        $route->get('post/{postId}/reactions', 'postReactions');
        $route->get('post/{postId}/type/{type}', 'reactionsByType');
        $route->get('reel/{reelId}/reactions', 'reelReactions');
        $route->get('reel/{reelId}/type/{type}', 'reactionsByTypeForReel');
        $route->post('', 'store');
        $route->get('{id}', 'show');
        $route->post('{id}', 'update');
        $route->delete('{id}', 'destroy');
        $route->post('post/{postId}/toggle', 'toggle');
        $route->get('post/{postId}/user-reaction', 'userReaction');
        $route->get('post/{postId}/count', 'count');
        $route->get('post/{postId}/summary', 'summary');
        $route->post('reel/{reelId}/toggle', 'toggleForReel');
        $route->get('reel/{reelId}/user-reaction', 'userReactionForReel');
        $route->get('reel/{reelId}/count', 'countForReel');
        $route->get('reel/{reelId}/summary', 'summaryForReel');
    });
    Route::controller(PostReportController::class)->prefix('post-reports')->group(function ($route) {
        $route->post('', 'create');
    });
});
