<?php

namespace Modules\Community\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\BaseResponse;
use Modules\Community\Http\Requests\User\CommentRequest;
use Modules\Community\Http\Resources\User\CommentResource;
use Modules\Community\Services\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use BaseResponse;

    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Get all comments
     */
    public function index(Request $request)
    {
        $filters = $request->only(['per_page', 'user_id', 'post_id', 'status']);
        $comments = $this->commentService->getAllComments($filters);

        return $this->successResponse(
            __('messages.comments.retrieved_successfully'),
            $comments
        );
    }

    /**
     * Get comment by ID
     */
    public function show($id)
    {
        $comment = $this->commentService->getCommentById($id);

        if (!$comment) {
            return $this->errorResponse(__('messages.comments.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.comments.retrieved_successfully'),
            new CommentResource($comment)
        );
    }

    /**
     * Create a new comment
     */
    public function store(CommentRequest $request)
    {
        $comment = $this->commentService->createComment($request->validated());

        return $this->successResponse(
            __('messages.comments.created_successfully'),
            $comment,
            201
        );
    }

    /**
     * Update a comment
     */
    public function update(CommentRequest $request, $id)
    {
        $comment = $this->commentService->updateComment($id, $request->validated());

        if (!$comment) {
            return $this->errorResponse(__('messages.comments.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.comments.updated_successfully'),
            new \App\Http\Resources\User\CommentResource($comment)
        );
    }

    /**
     * Delete a comment
     */
    public function destroy($id)
    {
        $deleted = $this->commentService->deleteComment($id);

        if (!$deleted) {
            return $this->errorResponse(__('messages.comments.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.comments.deleted_successfully'),
            null
        );
    }

    /**
     * Get my comments
     */
    public function myComments(Request $request)
    {
        $filters = $request->only(['per_page', 'status']);
        $comments = $this->commentService->getMyComments($filters);

        return $this->successResponse(
            __('messages.comments.my_comments_retrieved_successfully'),
            $comments
        );
    }

    /**
     * Get user comments
     */
    public function userComments($userId, Request $request)
    {
        $filters = $request->only(['per_page', 'status']);
        $comments = $this->commentService->getUserComments($userId, $filters);

        return $this->successResponse(
            __('messages.comments.user_comments_retrieved_successfully'),
            $comments
        );
    }

    /**
     * Get post comments
     */
    public function postComments($postId, Request $request)
    {
        $filters = $request->only(['per_page', 'status']);
        $comments = $this->commentService->getPostComments($postId, $filters);

        return $this->successResponse(
            __('messages.comments.post_comments_retrieved_successfully'),
            CommentResource::collection($comments)
        );
    }

    /**
     * Get reel comments
     */
    public function reelComments($reelId, Request $request)
    {
        $filters = $request->only(['per_page', 'status']);
        $comments = $this->commentService->getReelComments($reelId, $filters);

        return $this->successResponse(
            __('messages.comments.reel_comments_retrieved_successfully'),
            CommentResource::collection($comments)
        );
    }

    /**
     * Get comment replies
     */
    public function replies($id)
    {
        $replies = $this->commentService->getCommentReplies($id);

        return $this->successResponse(
            __('messages.comments.replies_retrieved_successfully'),
            $replies
        );
    }

    /**
     * Add reply to comment
     */
    public function addReply(CommentRequest $request, $id)
    {
        $reply = $this->commentService->addReply($id, $request->validated());

        if (!$reply) {
            return $this->errorResponse(__('messages.comments.parent_not_found'), 404);
        }

        return $this->successResponse(
            __('messages.comments.reply_created_successfully'),
            new CommentResource($reply),
            201
        );
    }

    /**
     * Search comments
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $filters = $request->only(['per_page', 'user_id', 'post_id']);

        if (!$query) {
            return $this->errorResponse(__('messages.comments.search_query_required'), 400);
        }

        $comments = $this->commentService->searchComments($query, $filters);

        return $this->successResponse(
            __('messages.comments.search_results_retrieved_successfully'),
            $comments
        );
    }
}
