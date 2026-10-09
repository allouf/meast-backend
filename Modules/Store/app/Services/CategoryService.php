<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Utilities\FileManager;

class CategoryService
{
    protected $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function all($search = null, $paginate = true)
    {
        return $this->categoryRepository->all($search, $paginate);
    }

    public function getActive(?string $search = null, ?string $sort = null, bool $paginate = true)
    {
        return $this->categoryRepository->getActive($search, $sort, $paginate);
    }
    public function getWithChildrens(?string $search = null)
    {
        return $this->categoryRepository->getWithChildrens($search);
    }
    public function findById($id)
    {
        return $this->categoryRepository->findById($id);
    }

    public function create(array $data)
    {
        if (isset($data['icon'])) {
            $data['icon'] = FileManager::upload('categories', $data['icon']);
        }
        $parentId = $data['parent_id'] ?? null;
        $maxSort = $this->categoryRepository->getMaxSortOrderByParent($parentId);
        $data['sort_order'] = $data['sort_order'] ?? ($maxSort + 1);
        return $this->categoryRepository->create($data);
    }

    public function update(array $data, $id)
    {
        if (isset($data['icon'])) {
            $category = $this->categoryRepository->findById($id);
            if ($category->icon && FileManager::fileExists($category->icon)) {
                FileManager::delete($category->icon);
            }
            $data['icon'] = FileManager::upload('categories', $data['icon']);
        }
        return $this->categoryRepository->update($data, $id);
    }

    public function delete($id)
    {
        $category = $this->categoryRepository->findById($id);
        if ($category->icon && FileManager::fileExists($category->icon)) {
            FileManager::delete($category->icon);
        }
        return $this->categoryRepository->delete($id);
    }
    public function switchActivation($id)
    {
        return $this->categoryRepository->switchActivation($id);
    }
    public function reorder($categoryId, $newSortOrder)
    {

        $category = $this->categoryRepository->findById($categoryId);
        $parentId = $category->parent_id;
        $siblings = $this->categoryRepository->getSiblings($parentId);

        $minSort = $siblings->min('sort_order');
        $maxSort = $siblings->max('sort_order');

        // التأكد أن الترتيب الجديد ضمن المدى
        if ($newSortOrder < $minSort || $newSortOrder > $maxSort) {
            abort(400, "Sort order out of range. Allowed: $minSort - $maxSort");
        }

        // نعيد ترتيب العناصر
        foreach ($siblings as $sibling) {
            if ($sibling->id == $categoryId) {
                $this->categoryRepository->updateSortOrder($sibling->id, $newSortOrder);
            } elseif ($sibling->sort_order >= $newSortOrder && $sibling->sort_order < $category->sort_order) {
                $this->categoryRepository->updateSortOrder($sibling->id, $sibling->sort_order + 1);
            } elseif ($sibling->sort_order <= $newSortOrder && $sibling->sort_order > $category->sort_order) {
                $this->categoryRepository->updateSortOrder($sibling->id, $sibling->sort_order - 1);
            }
        }

        return true;
    }
}
