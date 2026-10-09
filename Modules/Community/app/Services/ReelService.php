<?php

namespace Modules\Community\Services;

use App\Utilities\FileManager;
use Modules\Community\Models\Reel;
use Modules\Community\Repositories\Interfaces\CommentRepositoryInterface;
use Modules\Community\Repositories\Interfaces\ReactionRepositoryInterface;
use Modules\Community\Repositories\Interfaces\ReelRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ReelService
{
    protected $reelRepository;
    protected $commentRepository;
    protected $reactionRepository;
    protected $fileManager;

    public function __construct(
        ReelRepositoryInterface $reelRepository,
        CommentRepositoryInterface $commentRepository,
        ReactionRepositoryInterface $reactionRepository,
        FileManager $fileManager
    ) {
        $this->reelRepository = $reelRepository;
        $this->commentRepository = $commentRepository;
        $this->reactionRepository = $reactionRepository;
        $this->fileManager = $fileManager;
    }

    public function getAllReels($filters = [])
    {
        return $this->reelRepository->all($filters);
    }

    public function getActive()
    {
        return $this->reelRepository->getActive();
    }

    public function getReelById($id)
    {
        return $this->reelRepository->find($id);
    }

    public function createReel($data)
    {
        $data['user_id'] = Auth::id();
        if (isset($data['video']) && $data['video']) {
            $data['video_path'] = FileManager::upload('reels', $data['video']);
        }
        return $this->reelRepository->create($data);
    }

    public function updateReel($id, $data)
    {
        $reel = $this->reelRepository->find($id);
        if (!$reel || ($reel->user_id !== Auth::id() && !Auth::guard('admin')->check())) {
            return null;
        }

        if (isset($data['video']) && $data['video']) {
            if ($reel->video_path) {
                FileManager::delete($reel->video_path);
            }
            $data['video_path'] = FileManager::upload('reels', $data['video']);
        }

        return $this->reelRepository->update($id, $data);
    }

    public function deleteReel($id)
    {
        $reel = $this->reelRepository->find($id);
        if (!$reel || ($reel->user_id !== Auth::id() && !Auth::guard('admin')->check())) {
            return false;
        }
        if ($reel->video_path) {
            FileManager::delete($reel->video_path);
        }
        return $this->reelRepository->delete($id);
    }

    public function getUserReels($userId, $filters = [])
    {
        return $this->reelRepository->getByUser($userId, $filters);
    }

    public function getMyReels($filters = [])
    {
        return $this->reelRepository->getByUser(Auth::id(), $filters);
    }

    public function getFeaturedReels()
    {
        return $this->reelRepository->getFeatured();
    }

    public function searchReels($query, $filters = [])
    {
        return $this->reelRepository->search($query, $filters);
    }

    public function getReelWithDetails($id)
    {
        $reel = $this->reelRepository->find($id);
        if (!$reel) {
            return null;
        }
        // Get user's reaction to this reel
        $userReaction = $this->reactionRepository->getUserReactionForReel(Auth::id(), $id);
        $reactionsByType = $this->reactionRepository->getReactionsByTypeForReel($id);
        $reel->user_reaction = $userReaction;
        $reel->reactions_by_type = $reactionsByType;
        $reel->total_reactions = array_sum($reactionsByType);
        $reel->total_comments = $reel->comments_count;
        return $reel;
    }

    public function toggleReaction($reelId, $type)
    {
        $reel = $this->reelRepository->find($reelId);
        if (!$reel) {
            return null;
        }
        return $this->reactionRepository->toggleForReel(Auth::id(), $reelId, $type);
    }

    public function getReelReactions($reelId, $filters = [])
    {
        return $this->reactionRepository->getByReel($reelId, $filters);
    }

    public function getReelComments($reelId, $filters = [])
    {
        return $this->commentRepository->getByReel($reelId, $filters);
    }

    public function getReelTopLevelComments($reelId)
    {
        return $this->commentRepository->getTopLevelForReel($reelId);
    }

    public function addComment($reelId, $data)
    {
        $reel = $this->reelRepository->find($reelId);
        if (!$reel) {
            return null;
        }
        $data['user_id'] = Auth::id();
        $data['commentable_type'] = Reel::class;
        $data['commentable_id'] = $reelId;
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
        return $this->reelRepository->filter($data);
    }
}
