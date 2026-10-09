<?php

namespace Modules\Community\Services;

use App\Utilities\FileManager;
use Modules\Community\Models\Story;
use Modules\Community\Repositories\Interfaces\CommentRepositoryInterface;
use Modules\Community\Repositories\Interfaces\ReactionRepositoryInterface;
use Modules\Community\Repositories\Interfaces\StoryRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Carbon\Carbon;

class StoryService
{
    protected $storyRepository;
    protected $commentRepository;
    protected $reactionRepository;
    protected $fileManager;

    public function __construct(
        StoryRepositoryInterface $storyRepository,
        CommentRepositoryInterface $commentRepository,
        ReactionRepositoryInterface $reactionRepository,
        FileManager $fileManager
    ) {
        $this->storyRepository = $storyRepository;
        $this->commentRepository = $commentRepository;
        $this->reactionRepository = $reactionRepository;
        $this->fileManager = $fileManager;
    }

    public function getAllStories($filters = [])
    {
        return $this->storyRepository->all($filters);
    }

    public function getActive()
    {
        return $this->storyRepository->getActive();
    }

    public function getStoryById($id)
    {
        return $this->storyRepository->find($id);
    }

    public function createStory($data)
    {
        $data['user_id'] = Auth::id();

        if (!isset($data['expires_at'])) {
            $data['expires_at'] = Carbon::now()->addHours(24);
        }

        if (isset($data['media']) && $data['media']) {
            $data['media'] = FileManager::upload('stories', $data['media']);
        }

        return $this->storyRepository->create($data);
    }

    public function updateStory($id, $data)
    {
        $story = $this->storyRepository->find($id);
        if (!$story || ($story->user_id !== Auth::id() && !Auth::guard('admin')->check())) {
            return null;
        }

        if (isset($data['media']) && $data['media']) {
            if ($story->media) {
                FileManager::delete($story->media);
            }
            $data['media'] = FileManager::upload('stories', $data['media']);
        }

        return $this->storyRepository->update($id, $data);
    }

    public function deleteStory($id)
    {
        $story = $this->storyRepository->find($id);
        if (!$story || ($story->user_id !== Auth::id() && !Auth::guard('admin')->check())) {
            return false;
        }

        if ($story->media) {
            FileManager::delete($story->media);
        }

        return $this->storyRepository->delete($id);
    }

    public function getUserStories($userId, $filters = [])
    {
        return $this->storyRepository->getByUser($userId, $filters);
    }

    public function getMyStories($filters = [])
    {
        return $this->storyRepository->getByUser(Auth::id(), $filters);
    }

    public function getFeaturedStories()
    {
        return $this->storyRepository->getFeatured();
    }

    public function searchStories($query, $filters = [])
    {
        return $this->storyRepository->search($query, $filters);
    }

    public function getStoryWithDetails($id)
    {
        $story = $this->storyRepository->find($id);
        if (!$story) {
            return null;
        }

        $userReaction = $this->reactionRepository->getUserReaction(Auth::id(), $id, Story::class);
        $reactionsByType = $this->reactionRepository->getReactionsByType($id, Story::class);

        $story->user_reaction = $userReaction;
        $story->reactions_by_type = $reactionsByType;
        $story->total_reactions = array_sum($reactionsByType);
        $story->total_replies = $this->commentRepository->getByStory($id)->total();

        return $story;
    }

    public function toggleReaction($storyId, $type)
    {
        $story = $this->storyRepository->find($storyId);
        if (!$story) {
            throw new NotFoundHttpException('Story not found');
        }

        return $this->reactionRepository->toggle(Auth::id(), $storyId, $type, Story::class);
    }

    public function getStoryReactions($storyId)
    {
        return $this->reactionRepository->getByStory($storyId);
    }

    public function getStoryReplies($storyId, $filters = [])
    {
        return $this->commentRepository->getByStory($storyId, $filters);
    }

    public function getStoryTopLevelReplies($storyId)
    {
        return $this->commentRepository->getTopLevelForStory($storyId);
    }

    public function getStoryViewers($storyId)
    {
        return $this->storyRepository->getStoryViews($storyId);
    }

    public function addReply($storyId, $data)
    {
        $story = $this->storyRepository->find($storyId);
        if (!$story) {
            return null;
        }

        $data['user_id'] = Auth::id();
        $data['commentable_type'] = Story::class;
        $data['commentable_id'] = $storyId;

        return $this->commentRepository->create($data);
    }

    public function updateReply($replyId, $data)
    {
        $reply = $this->commentRepository->find($replyId);
        if (!$reply || $reply->user_id !== Auth::id()) {
            return null;
        }

        return $this->commentRepository->update($replyId, $data);
    }

    public function deleteReply($replyId)
    {
        $reply = $this->commentRepository->find($replyId);
        if (!$reply || $reply->user_id !== Auth::id()) {
            return false;
        }

        return $this->commentRepository->delete($replyId);
    }

    public function getReplyReplies($replyId)
    {
        return $this->commentRepository->getReplies($replyId);
    }

    public function addReplyToReply($replyId, $data)
    {
        $parentReply = $this->commentRepository->find($replyId);
        if (!$parentReply) {
            return null;
        }

        $data['user_id'] = Auth::id();
        $data['commentable_type'] = $parentReply->commentable_type;
        $data['commentable_id'] = $parentReply->commentable_id;
        $data['parent_id'] = $replyId;

        return $this->commentRepository->create($data);
    }

    public function filter($data)
    {
        return $this->storyRepository->filter($data);
    }

    public function getNotExpired($filters = [])
    {
        return $this->storyRepository->getNotExpired($filters);
    }

    public function deleteExpiredStories()
    {
        return $this->storyRepository->deleteExpiredStories();
    }

    public function getMostViewed($limit = 10)
    {
        return $this->storyRepository->getMostViewed($limit);
    }

    public function viewStory($storyId)
    {
        $story = $this->storyRepository->find($storyId);
        if (!$story) {
            return null;
        }

        $userId = Auth::id();

        if (!$story->hasBeenViewedBy($userId)) {
            $this->storyRepository->addStoryView($storyId, $userId);
        }

        return $this->getStoryWithDetails($storyId);
    }
}
