<?php

namespace App\Services;

use Modules\Store\Services\CategoryService;

class HomeService
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function indexPageData($perPage)
    {
        return [
            'countries' => [],
        ];
    }

    public function getHomePageData($regionId, $categoryId)
    {
        return [
            'sliders' => [],
            'categories' => $this->categoryService->findById($categoryId),
        ];
    }
}
