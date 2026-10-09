<?php

namespace Modules\Community\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Community\Http\Requests\User\CommentRequest;
use Modules\Community\Http\Requests\User\PostRequest;
use Modules\Community\Http\Resources\User\CommentResource;
use Modules\Community\Http\Resources\User\PostResource;
use Modules\Community\Http\Resources\User\ReactionResource;
use Modules\Community\Services\CommentService;
use Modules\Community\Services\PostService;
use Modules\Community\Services\ReactionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{

    protected $postService;
    protected $commentService;
    protected $reactionService;

    public function __construct(
        PostService $postService,
        CommentService $commentService,
        ReactionService $reactionService
    ) {
        $this->postService = $postService;
        $this->commentService = $commentService;
        $this->reactionService = $reactionService;
    }

    /**
     * Get all posts
     */
    public function index(Request $request)
    {
        $filters = $request->only(['per_page', 'search', 'user_id']);
        $posts = $this->postService->getAllPosts($filters);

        // Check if posts is already paginated (has 'data' key)
        if (is_array($posts) && isset($posts['data'])) {
            return $this->successResponse(
                __('messages.posts.retrieved_successfully'),
                [
                    'data' => PostResource::collection($posts['data']),
                    'meta' => $posts['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.posts.retrieved_successfully'),
            PostResource::collection($posts)
        );
    }
    /**
     * Get all posts
     */
    public function allPosts(Request $request)
    {
        $filters = $request->only(['per_page', 'search', 'user_id']);
        $posts = $this->postService->getAllPosts($filters);

        // Check if posts is already paginated (has 'data' key)
        if (is_array($posts) && isset($posts['data'])) {
            return $this->successResponse(
                __('messages.posts.retrieved_successfully'),
                [
                    'data' => PostResource::collection($posts['data']),
                    'meta' => $posts['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.posts.retrieved_successfully'),
            PostResource::collection($posts)
        );
    }

    /**
     * Get featured posts
     */
    public function featured()
    {
        $posts = $this->postService->getFeaturedPosts();

        return $this->successResponse(
            __('messages.posts.featured_retrieved_successfully'),
            PostResource::collection($posts)
        );
    }

    /**
     * Get post by ID with details
     */
    public function show($id)
    {
        $post = $this->postService->getPostWithDetails($id);

        if (!$post) {
            return $this->errorResponse(__('messages.posts.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.posts.retrieved_successfully'),
            new PostResource($post)
        );
    }

    /**
     * Create a new post
     */
    public function store(PostRequest $request)
    {

        $data = $request->validated();
        if (!isset($data['country_code']) && Auth::user()->country_code) {
            $data['country_code'] = Auth::user()->country_code;
        }
        if (!isset($data['region']) && Auth::user()->region) {
            $data['region'] = Auth::user()->region;
        }
        try {
            $post = $this->postService->createPost($data);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }

        return $this->successResponse(
            __('messages.posts.created_successfully'),
            new PostResource($post),
            201
        );
    }

    /**
     * Update a post
     */
    public function update(PostRequest $request, $id)
    {
        $post = $this->postService->updatePost($id, $request->validated());

        if (!$post) {
            return $this->errorResponse(__('messages.posts.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.posts.updated_successfully'),
            new PostResource($post)
        );
    }


    public function destroy($id)
    {
        $deleted = $this->postService->deletePost($id);

        if (!$deleted) {
            return $this->errorResponse(__('messages.posts.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.posts.deleted_successfully'),
            null
        );
    }

    /**
     * Get my posts
     */
    public function myPosts(Request $request)
    {
        $filters = $request->only(['per_page', 'status']);
        $posts = $this->postService->getMyPosts($filters);

        // Check if posts is already paginated (has 'data' key)
        if (is_array($posts) && isset($posts['data'])) {
            return $this->successResponse(
                __('messages.posts.my_posts_retrieved_successfully'),
                [
                    'data' => PostResource::collection($posts['data']),
                    'meta' => $posts['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.posts.my_posts_retrieved_successfully'),
            PostResource::collection($posts)
        );
    }

    /**
     * Get user posts
     */
    public function userPosts(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $filters = $request->only(['per_page', 'status']);

        $posts = $this->postService->getUserPosts($request->user_id, $filters);

        // Check if posts is already paginated (has 'data' key)
        if (is_array($posts) && isset($posts['data'])) {
            return $this->successResponse(
                __('messages.posts.user_posts_retrieved_successfully'),
                [
                    'data' => PostResource::collection($posts['data']),
                    'meta' => $posts['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.posts.user_posts_retrieved_successfully'),
            PostResource::collection($posts)
        );
    }

    /**
     * Search posts
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $filters = $request->only(['per_page', 'user_id']);

        if (!$query) {
            return $this->errorResponse(__('messages.posts.search_query_required'), 400);
        }

        $posts = $this->postService->searchPosts($query, $filters);

        // Check if posts is already paginated (has 'data' key)
        if (is_array($posts) && isset($posts['data'])) {
            return $this->successResponse(
                __('messages.posts.search_results_retrieved_successfully'),
                [
                    'data' => PostResource::collection($posts['data']),
                    'meta' => $posts['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.posts.search_results_retrieved_successfully'),
            PostResource::collection($posts)
        );
    }

    /**
     * Toggle reaction on post
     */
    public function toggleReaction(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:like,love,haha,wow,sad,angry'
        ]);

        $reaction = $this->postService->toggleReaction($id, $request->type);

        return $this->successResponse(
            $reaction ? __('messages.reactions.toggled_successfully') : __('messages.reactions.removed_successfully'),
            $reaction ? new ReactionResource($reaction) : null
        );
    }

    /**
     * Get post reactions
     */
    public function reactions($id, Request $request)
    {
        $filters = $request->only(['per_page', 'type']);
        $reactions = $this->postService->getPostReactions($id, $filters);

        return $this->successResponse(
            __('messages.reactions.retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Get post comments
     */
    public function comments($id, Request $request)
    {
        $filters = $request->only(['per_page']);
        $comments = $this->postService->getPostComments($id, $filters);

        return $this->successResponse(
            __('messages.comments.retrieved_successfully'),
            CommentResource::collection($comments)
        );
    }

    /**
     * Get post top-level comments
     */
    public function topLevelComments($id)
    {
        $comments = $this->postService->getPostTopLevelComments($id);

        return $this->successResponse(
            __('messages.comments.top_level_retrieved_successfully'),
            CommentResource::collection($comments)
        );
    }

    /**
     * Add comment to post
     */
    public function addComment(CommentRequest $request, $id)
    {
        $comment = $this->postService->addComment($id, $request->validated());

        if (!$comment) {
            return $this->errorResponse(__('messages.posts.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.comments.created_successfully'),
            new CommentResource($comment),
            201
        );
    }
}
