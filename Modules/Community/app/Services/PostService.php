<?php

namespace Modules\Community\Services;

use App\Utilities\FileManager;
use Modules\Community\Repositories\Interfaces\CommentRepositoryInterface;
use Modules\Community\Repositories\Interfaces\PostRepositoryInterface;
use Modules\Community\Repositories\Interfaces\ReactionRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class PostService
{
    protected $postRepository;
    protected $commentRepository;
    protected $reactionRepository;
    protected $fileManager;
    public function __construct(
        PostRepositoryInterface $postRepository,
        CommentRepositoryInterface $commentRepository,
        ReactionRepositoryInterface $reactionRepository,
        FileManager $fileManager
    ) {
        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
        $this->reactionRepository = $reactionRepository;
        $this->fileManager = $fileManager;
    }
    public function getAllPosts($filters = [])
    {
        return $this->postRepository->all($filters);
    }
    public function getActive()
    {
        return $this->postRepository->getActive();
    }
    public function getPostById($id)
    {
        return $this->postRepository->find($id);
    }
    public function createPost($data)
    {
        $data['user_id'] = Auth::id();
        if (isset($data['image']) && $data['image']) {
            $data['image'] = FileManager::upload('posts', $data['image']);
        }
        return $this->postRepository->create($data);
    }
    public function updatePost($id, $data)
    {
        $post = $this->postRepository->find($id);
        if (!$post || ($post->user_id !== Auth::id() && !Auth::guard('admin')->check())) {
            return null;
        }
        $oldValues = $post->only(array_keys($data));
        if (isset($data['image']) && $data['image']) {
            if ($post->image) {
                FileManager::delete($post->image);
            }
            $data['image'] = FileManager::upload('posts', $data['image']);
        }
        $updatedPost = $this->postRepository->update($id, $data);
        $newValues = $updatedPost->only(array_keys($data));
        $this->logPostChange($updatedPost->id, Auth::id(), $oldValues, $newValues);
        return $updatedPost;
    }
    protected function logPostChange($postId, $userId, $oldValues, $newValues)
    {
        $changes = [];
        foreach ($oldValues as $key => $oldValue) {
            $newValue = $newValues[$key] ?? null;
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }
        if (!empty($changes)) {
            $this->postRepository->logPostChange($postId, $userId, $changes);
        }
    }
    public function deletePost($id)
    {
        $post = $this->postRepository->find($id);
        if (!$post || ($post->user_id !== Auth::id() && !Auth::guard('admin')->check())) {
            return false;
        }
        if ($post->image) {
            FileManager::delete($post->image);
        }
        return $this->postRepository->delete($id);
    }
    public function getUserPosts($userId, $filters = [])
    {
        return $this->postRepository->getByUser($userId, $filters);
    }
    public function getMyPosts($filters = [])
    {
        return $this->postRepository->getByUser(Auth::id(), $filters);
    }
    public function getFeaturedPosts()
    {
        return $this->postRepository->getFeatured();
    }
    public function searchPosts($query, $filters = [])
    {
        return $this->postRepository->search($query, $filters);
    }
    public function getPostWithDetails($id)
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return null;
        }
        // Get user's reaction to this post
        $userReaction = $this->reactionRepository->getUserReaction(Auth::id(), $id);
        $reactionsByType = $this->reactionRepository->getReactionsByType($id);
        $post->user_reaction = $userReaction;
        $post->reactions_by_type = $reactionsByType;
        $post->total_reactions = array_sum($reactionsByType);
        $post->total_comments = $this->commentRepository->getByPost($id)->total();
        return $post;
    }
    public function toggleReaction($postId, $type)
    {
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return null;
        }
        return $this->reactionRepository->toggle(Auth::id(), $postId, $type);
    }
    public function getPostReactions($postId)
    {
        return $this->reactionRepository->getByPost($postId);
    }
    public function getPostComments($postId, $filters = [])
    {
        return $this->commentRepository->getByPost($postId, $filters);
    }
    public function getPostTopLevelComments($postId)
    {
        return $this->commentRepository->getTopLevelForPost($postId);
    }
    public function addComment($postId, $data)
    {
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return null;
        }
        $data['user_id'] = Auth::id();
        $data['commentable_type'] = \Modules\Community\Models\Post::class;
        $data['commentable_id'] = $postId;
        return $this->commentRepository->create($data);
    }
    public function updateComment($commentId, $data)
    {
        $comment = $this->commentRepository->find($commentId);
        if (!$comment || $comment->user_id !== Auth::id()) {
            return null;
        }
        return $this->commentRepository->update($commentId, $data);
    }
    public function deleteComment($commentId)
    {
        $comment = $this->commentRepository->find($commentId);
        if (!$comment || $comment->user_id !== Auth::id()) {
            return false;
        }
        return $this->commentRepository->delete($commentId);
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
    public function filter($data)
    {
        return $this->postRepository->filter($data);
    }
}
