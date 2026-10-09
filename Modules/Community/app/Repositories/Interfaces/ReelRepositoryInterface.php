<?php

namespace Modules\Community\Repositories\Interfaces;

interface ReelRepositoryInterface
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
    public function getReactionsCount($reelId);
    public function getCommentsCount($reelId);
    public function filter($data);
}
