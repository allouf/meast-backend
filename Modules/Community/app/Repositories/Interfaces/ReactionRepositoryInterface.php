<?php

namespace Modules\Community\Repositories\Interfaces;

use Modules\Community\Models\Post;
use Modules\Community\Models\Reel;
use Modules\Community\Models\Story;

interface ReactionRepositoryInterface
{
    public function all($filters = []);
    public function find($id);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
    public function getByUser($userId, $filters = []);
    public function getByPost($postId, $filters = []);
    public function getByType($postId, $type);
    public function toggle($userId, $reactionableId, $type, $reactionableType = Post::class);
    public function getUserReaction($userId, $reactionableId, $reactionableType = Post::class);
    public function getReactionsCount($postId);
    public function getReactionsByType($reactionableId, $reactionableType = Post::class);
    public function getByReel($reelId, $filters = []);
    public function toggleForReel($userId, $reelId, $type);
    public function getUserReactionForReel($userId, $reelId);
    public function getReactionsCountForReel($reelId);
    public function getReactionsByTypeForReel($reelId);
    public function getByTypeForReel($reelId, $type);
    public function getByStory($storyId, $filters = []);
    public function toggleForStory($userId, $storyId, $type);
    public function getUserReactionForStory($userId, $storyId);
    public function getReactionsCountForStory($storyId);
    public function getReactionsByTypeForStory($storyId);
    public function getByTypeForStory($storyId, $type);
}
