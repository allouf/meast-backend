<?php

namespace Modules\Community\Services;

use Modules\Community\Repositories\Interfaces\CommentRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CommentService
{
    protected $commentRepository;

    public function __construct(CommentRepositoryInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    public function getAllComments($filters = [])
    {
        return $this->commentRepository->getActive($filters);
    }

    public function getCommentById($id)
    {
        return $this->commentRepository->find($id);
    }

    public function createComment($data)
    {
        $data['user_id'] = Auth::id();
        return $this->commentRepository->create($data);
    }

    public function updateComment($id, $data)
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment || $comment->user_id !== Auth::id()) {
            return null;
        }

        return $this->commentRepository->update($id, $data);
    }

    public function deleteComment($id)
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment || $comment->user_id !== Auth::id()) {
            return false;
        }

        return $this->commentRepository->delete($id);
    }

    public function getUserComments($userId, $filters = [])
    {
        return $this->commentRepository->getByUser($userId, $filters);
    }

    public function getMyComments($filters = [])
    {
        return $this->commentRepository->getByUser(Auth::id(), $filters);
    }

    public function getPostComments($postId, $filters = [])
    {
        return $this->commentRepository->getByPost($postId, $filters);
    }

    public function getReelComments($reelId, $filters = [])
    {
        return $this->commentRepository->getByReel($reelId, $filters);
    }

    public function getTopLevelComments($postId)
    {
        return $this->commentRepository->getTopLevel($postId);
    }

    public function getCommentReplies($commentId)
    {
        return $this->commentRepository->getReplies($commentId);
    }

    public function addReply($commentId, $data)
    {
        $parentComment = $this->commentRepository->find($commentId);

        if (!$parentComment) {
            return null;
        }

        $data['user_id'] = Auth::id();
        $data['commentable_type'] = $parentComment->commentable_type;
        $data['commentable_id'] = $parentComment->commentable_id;
        $data['parent_id'] = $commentId;

        return $this->commentRepository->create($data);
    }

    public function searchComments($query, $filters = [])
    {
        return $this->commentRepository->search($query, $filters);
    }
}
