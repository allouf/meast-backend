<?php

namespace Modules\Community\Repositories\Interfaces;

interface StoryRepositoryInterface
{
    public function all($filters = []);
    public function find($id);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
    public function getByUser($userId, $filters = []);
    public function getActive($filters = []);
    public function getFeatured();
    public function search($query, $filters = []);
    public function getWithRelations($id, $relations = []);
    public function getReactionsCount($storyId);
    public function getRepliesCount($storyId);
    public function filter($data);
    public function getNotExpired($filters = []);
    public function getExpiredStories();
    public function deleteExpiredStories();
    public function incrementViews($storyId);
    public function getMostViewed($limit = 10);
    public function addStoryView($storyId, $userId);
    public function getStoryViews($storyId);
}
