<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Store\Exports\CategoryExport;
use Modules\Store\Http\Requests\Admin\CategoryRequest;
use Modules\Store\Http\Resources\Admin\CategoryResource;
use Modules\Store\Services\CategoryService;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    public function all(Request $request)
    {
        $paginate = $request->boolean('paginate', false);
        $search = $request->get('search');

        $categories = $this->categoryService->all($search, $paginate);

        $response = [
            'categories' => CategoryResource::collection($categories['data'])
        ];

        if ($paginate && $categories['meta']) {
            $response['meta'] = $categories['meta'];
        }

        return $this->successResponse(__('fetch_success'), $response);
    }

    public function create(CategoryRequest $request)
    {
        try {
            $category = $this->categoryService->create($request->validated());
            return $this->successResponse(__('create_success'), new CategoryResource($category));
        } catch (Exception $e) {
            return  $this->exceptionResponse($e);
        }
    }
    public function update(CategoryRequest $request, $id)
    {
        try {
            $category = $this->categoryService->update($request->validated(), $id);
            return $this->successResponse(__('update_success'), new CategoryResource($category));
        } catch (Exception $e) {
            return  $this->exceptionResponse($e);
        }
    }
    public function delete($id)
    {
        try {
            $this->categoryService->delete($id);
            return $this->successResponse(__('delete_success'));
        } catch (Exception $e) {
            return  $this->exceptionResponse($e);
        }
    }
    public function switchActivation($id)
    {
        try {
            $this->categoryService->switchActivation($id);
            return $this->successResponse(__('switch_success'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function reorder(Request $request, $id)
    {
        try {
            $this->categoryService->reorder($id, $request->input('new_order'));
            return $this->successResponse(__('Category reordered successfully'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function export()
    {
        return Excel::download(new CategoryExport, 'categories.xlsx');
    }
}
