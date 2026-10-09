<?php

namespace Modules\Community\Repositories\Interfaces;

interface PostRepositoryInterface
{
    public function all($filters = []);
    public function find($id);
    public function create($data);
    public function update($id, $data);
    public function logPostChange($postId, $userId, $changes);
    public function delete($id);
    public function getByUser($userId, $filters = []);
    public function getActive($filters = []);
    public function getFeatured();
    public function search($query, $filters = []);
    public function getWithRelations($id, $relations = []);
    public function getReactionsCount($postId);
    public function getCommentsCount($postId);
    public function filter($data);
}
