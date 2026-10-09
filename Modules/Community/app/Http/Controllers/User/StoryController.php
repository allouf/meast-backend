<?php

namespace Modules\Community\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Community\Http\Requests\User\CommentRequest;
use Modules\Community\Http\Requests\User\StoryRequest;
use Modules\Community\Http\Resources\User\CommentResource;
use Modules\Community\Http\Resources\User\ReactionResource;
use Modules\Community\Http\Resources\User\StoryResource;
use Modules\Community\Services\CommentService;
use Modules\Community\Services\ReactionService;
use Modules\Community\Services\StoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoryController extends Controller
{
    protected $storyService;
    protected $commentService;
    protected $reactionService;

    public function __construct(
        StoryService $storyService,
        CommentService $commentService,
        ReactionService $reactionService
    ) {
        $this->storyService = $storyService;
        $this->commentService = $commentService;
        $this->reactionService = $reactionService;
    }

    /**
     * Get all stories
     */
    public function index(Request $request)
    {
        $filters = $request->only(['per_page', 'search', 'user_id', 'type']);
        $stories = $this->storyService->getAllStories($filters);

        // Check if stories is already paginated (has 'data' key)
        if (is_array($stories) && isset($stories['data'])) {
            return $this->successResponse(
                __('messages.stories.retrieved_successfully'),
                [
                    'data' => StoryResource::collection($stories['data']),
                    'meta' => $stories['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.stories.retrieved_successfully'),
            StoryResource::collection($stories)
        );
    }

    /**
     * Get all stories (public)
     */
    public function allStories(Request $request)
    {
        $filters = $request->only(['per_page', 'search', 'user_id', 'type']);
        $stories = $this->storyService->getAllStories($filters);

        // Check if stories is already paginated (has 'data' key)
        if (is_array($stories) && isset($stories['data'])) {
            return $this->successResponse(
                __('messages.stories.retrieved_successfully'),
                [
                    'data' => StoryResource::collection($stories['data']),
                    'meta' => $stories['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.stories.retrieved_successfully'),
            StoryResource::collection($stories)
        );
    }

    /**
     * Get featured stories
     */
    public function featured()
    {
        $stories = $this->storyService->getFeaturedStories();

        return $this->successResponse(
            __('messages.stories.featured_retrieved_successfully'),
            StoryResource::collection($stories)
        );
    }

    /**
     * Get most viewed stories
     */
    public function mostViewed(Request $request)
    {
        $limit = $request->get('limit', 10);
        $stories = $this->storyService->getMostViewed($limit);

        return $this->successResponse(
            __('messages.stories.most_viewed_retrieved_successfully'),
            StoryResource::collection($stories)
        );
    }

    /**
     * Get story by ID with details
     */
    public function show($id)
    {
        $story = $this->storyService->getStoryWithDetails($id);

        if (!$story) {
            return $this->errorResponse(__('messages.stories.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.stories.retrieved_successfully'),
            new StoryResource($story)
        );
    }

    /**
     * View story (increments view count)
     */
    public function view($id)
    {
        $story = $this->storyService->viewStory($id);

        if (!$story) {
            return $this->errorResponse(__('messages.stories.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.stories.viewed_successfully'),
            new StoryResource($story)
        );
    }

    /**
     * Create a new story
     */
    public function store(StoryRequest $request)
    {
        $data = $request->validated();
        if (!isset($data['country_code']) && Auth::user()->country_code) {
            $data['country_code'] = Auth::user()->country_code;
        }
        if (!isset($data['region']) && Auth::user()->region) {
            $data['region'] = Auth::user()->region;
        }
        try {
            $story = $this->storyService->createStory($data);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }

        return $this->successResponse(
            __('messages.stories.created_successfully'),
            new StoryResource($story),
            201
        );
    }

    /**
     * Update a story
     */
    public function update(StoryRequest $request, $id)
    {
        $story = $this->storyService->updateStory($id, $request->validated());

        if (!$story) {
            return $this->errorResponse(__('messages.stories.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.stories.updated_successfully'),
            new StoryResource($story)
        );
    }

    /**
     * Delete a story
     */
    public function destroy($id)
    {
        $deleted = $this->storyService->deleteStory($id);

        if (!$deleted) {
            return $this->errorResponse(__('messages.stories.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.stories.deleted_successfully'),
            null
        );
    }

    /**
     * Get my stories
     */
    public function myStories(Request $request)
    {
        $filters = $request->only(['per_page', 'status', 'type']);
        $stories = $this->storyService->getMyStories($filters);

        // Check if stories is already paginated (has 'data' key)
        if (is_array($stories) && isset($stories['data'])) {
            return $this->successResponse(
                __('messages.stories.my_stories_retrieved_successfully'),
                [
                    'data' => StoryResource::collection($stories['data']),
                    'meta' => $stories['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.stories.my_stories_retrieved_successfully'),
            StoryResource::collection($stories)
        );
    }

    /**
     * Get user stories
     */
    public function userStories(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $filters = $request->only(['per_page', 'status', 'type']);

        $stories = $this->storyService->getUserStories($request->user_id, $filters);

        // Check if stories is already paginated (has 'data' key)
        if (is_array($stories) && isset($stories['data'])) {
            return $this->successResponse(
                __('messages.stories.user_stories_retrieved_successfully'),
                [
                    'data' => StoryResource::collection($stories['data']),
                    'meta' => $stories['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.stories.user_stories_retrieved_successfully'),
            StoryResource::collection($stories)
        );
    }

    /**
     * Search stories
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $filters = $request->only(['per_page', 'user_id', 'type']);

        if (!$query) {
            return $this->errorResponse(__('messages.stories.search_query_required'), 400);
        }

        $stories = $this->storyService->searchStories($query, $filters);

        // Check if stories is already paginated (has 'data' key)
        if (is_array($stories) && isset($stories['data'])) {
            return $this->successResponse(
                __('messages.stories.search_results_retrieved_successfully'),
                [
                    'data' => StoryResource::collection($stories['data']),
                    'meta' => $stories['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.stories.search_results_retrieved_successfully'),
            StoryResource::collection($stories)
        );
    }

    /**
     * Toggle reaction on story
     */
    public function toggleReaction(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:like,love,haha,wow,sad,angry'
        ]);
        try {
            $reaction = $this->storyService->toggleReaction($id, $request->type);
            return $this->successResponse(
                $reaction ? __('messages.reactions.toggled_successfully') : __('messages.reactions.removed_successfully'),
                $reaction ? new ReactionResource($reaction) : null
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }


    }

    /**
     * Get story reactions
     */
    public function reactions($id, Request $request)
    {
        $filters = $request->only(['per_page', 'type']);
        $reactions = $this->storyService->getStoryReactions($id, $filters);

        return $this->successResponse(
            __('messages.reactions.retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Get story replies
     */
    public function replies($id, Request $request)
    {
        $filters = $request->only(['per_page']);
        $replies = $this->storyService->getStoryReplies($id, $filters);

        return $this->successResponse(
            __('messages.replies.retrieved_successfully'),
            CommentResource::collection($replies)
        );
    }

    /**
     * Get story top-level replies
     */
    public function topLevelReplies($id)
    {
        $replies = $this->storyService->getStoryTopLevelReplies($id);

        return $this->successResponse(
            __('messages.replies.top_level_retrieved_successfully'),
            CommentResource::collection($replies)
        );
    }

    /**
     * Add reply to story
     */
    public function addReply(CommentRequest $request, $id)
    {
        $reply = $this->storyService->addReply($id, $request->validated());

        if (!$reply) {
            return $this->errorResponse(__('messages.stories.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.replies.created_successfully'),
            new CommentResource($reply),
            201
        );
    }

    /**
     * Get story viewers
     */
    public function viewers($id)
    {
        $viewers = $this->storyService->getStoryViewers($id);

        return $this->successResponse(
            __('messages.stories.viewers_retrieved_successfully'),
            $viewers
        );
    }
}
