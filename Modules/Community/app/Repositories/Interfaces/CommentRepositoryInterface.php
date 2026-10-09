<?php

namespace Modules\Community\Repositories\Interfaces;

interface CommentRepositoryInterface
{
    public function all($filters = []);
    public function find($id);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
    public function getByUser($userId, $filters = []);
    public function getByPost($postId, $filters = []);
    public function getTopLevelForPost($postId);
    public function getReplies($commentId);
    public function getActive($filters = []);
    public function search($query, $filters = []);
    public function getWithRelations($id, $relations = []);
    public function getByReel($reelId, $filters = []);
    public function getTopLevelForReel($reelId);
    public function getByStory($storyId, $filters = []);
    public function getTopLevelForStory($storyId);
}
