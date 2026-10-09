<?php

namespace Modules\Community\Repositories\Interfaces;

interface PostReportRepositoryInterface
{
    public function all();
    public function findById($id);
    public function create(array $data);
    public function delete($id);
    public function getByPost($postId);
}
