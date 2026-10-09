<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\CategoryResource;
use Modules\Store\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function getCategories(Request $request)
    {

        $search = $request->input('search');
        $sort   = $request->input('sort');
        $paginate = $request->boolean('paginate', true);

        $categories = $this->categoryService->getActive($search, $sort, $paginate);
        return $this->successResponse(__('store::messages.fetch_success'), [
            'categories' => CategoryResource::collection($categories['data']),
            'meta' => $categories['meta'] ?? null
        ]);
    }
    public function getWithChildrens(Request $request)
    {
        $categories = $this->categoryService->getWithChildrens(
            $request->query('search')
        );
        return $this->successResponse(
            __('store::messages.fetch_success'),
            CategoryResource::collection($categories),
        );
    }
    public function getChildrens($categoryId)
    {
        $category = $this->categoryService->findById($categoryId);
        return $this->successResponse(__('store::messages.fetch_success'), CategoryResource::collection($category['children']));
    }
}
