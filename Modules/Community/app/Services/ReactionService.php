<?php

namespace Modules\Community\Services;

use Modules\Community\Http\Resources\User\ReactionResource;
use Modules\Community\Repositories\Interfaces\ReactionRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ReactionService
{
    protected $reactionRepository;

    public function __construct(ReactionRepositoryInterface $reactionRepository)
    {
        $this->reactionRepository = $reactionRepository;
    }

    public function getAllReactions($filters = [])
    {
        return $this->reactionRepository->all($filters);
    }

    public function getReactionById($id)
    {
        return $this->reactionRepository->find($id);
    }

    public function createReaction($data)
    {
        $data['user_id'] = Auth::id();
        return $this->reactionRepository->create($data);
    }

    public function updateReaction($id, $data)
    {
        $reaction = $this->reactionRepository->find($id);

        if (!$reaction || $reaction->user_id !== Auth::id()) {
            return null;
        }

        return $this->reactionRepository->update($id, $data);
    }

    public function deleteReaction($id)
    {
        $reaction = $this->reactionRepository->find($id);

        if (!$reaction || $reaction->user_id !== Auth::id()) {
            return false;
        }

        return $this->reactionRepository->delete($id);
    }

    public function getUserReactions($userId, $filters = [])
    {
        return $this->reactionRepository->getByUser($userId, $filters);
    }

    public function getMyReactions($filters = [])
    {
        return $this->reactionRepository->getByUser(Auth::id(), $filters);
    }

    public function getPostReactions($postId, $filters = [])
    {
        return $this->reactionRepository->getByPost($postId, $filters);
    }

    public function getReactionsByType($postId, $type)
    {
        return $this->reactionRepository->getByType($postId, $type);
    }

    public function getReactionsSummaryByType($postId)
    {
        return $this->reactionRepository->getReactionsByType($postId);
    }

    public function toggleReaction($postId, $type)
    {
        return $this->reactionRepository->toggle(Auth::id(), $postId, $type);
    }

    public function getUserReaction($postId)
    {
        return $this->reactionRepository->getUserReaction(Auth::id(), $postId);
    }

    public function getReactionsCount($postId)
    {
        return $this->reactionRepository->getReactionsCount($postId);
    }

    public function getReactionsSummary($postId)
    {
        $reactionsByType = $this->reactionRepository->getReactionsByType($postId);
        $userReaction = $this->reactionRepository->getUserReaction(Auth::id(), $postId);

        return [
            'reactions_by_type' => $reactionsByType,
            'total_reactions' => array_sum($reactionsByType),
            'user_reaction' => new ReactionResource($userReaction)
        ];
    }

    // New methods for reels
    public function getReelReactions($reelId, $filters = [])
    {
        return $this->reactionRepository->getByReel($reelId, $filters);
    }

    public function getReactionsByTypeForReel($reelId, $type)
    {
        return $this->reactionRepository->getByTypeForReel($reelId, $type);
    }

    public function toggleReactionForReel($reelId, $type)
    {
        return $this->reactionRepository->toggleForReel(Auth::id(), $reelId, $type);
    }

    public function getUserReactionForReel($reelId)
    {
        return $this->reactionRepository->getUserReactionForReel(Auth::id(), $reelId);
    }

    public function getReactionsCountForReel($reelId)
    {
        return $this->reactionRepository->getReactionsCountForReel($reelId);
    }

    public function getReactionsSummaryForReel($reelId)
    {
        $reactionsByType = $this->reactionRepository->getReactionsByTypeForReel($reelId);
        $userReaction = $this->reactionRepository->getUserReactionForReel(Auth::id(), $reelId);

        return [
            'reactions_by_type' => $reactionsByType,
            'total_reactions' => array_sum($reactionsByType),
            'user_reaction' => new ReactionResource($userReaction)
        ];
    }
}
