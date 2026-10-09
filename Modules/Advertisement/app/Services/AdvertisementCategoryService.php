<?php

namespace Modules\Advertisement\Services;

use Illuminate\Support\Str;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementCategoryRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertisementCategoryService
{
    public function __construct(
        protected AdvertisementCategoryRepositoryInterface $categoryRepository
    ) {}

    public function getAllCategories($filters = [])
    {
        return $this->categoryRepository->all($filters);
    }

    public function getCategoryById($id)
    {
        return $this->categoryRepository->find($id);
    }

    public function getCategoryBySlug($slug)
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    public function createCategory($data)
    {
        if (!isset($data['slug']) && isset($data['name'])) {
            $name = is_array($data['name']) ? $data['name']['en'] ?? reset($data['name']) : $data['name'];
            $data['slug'] = Str::slug($name);
        }
        return $this->categoryRepository->create($data);
    }

    public function updateCategory($id, $data)
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return null;
        }
        if (isset($data['name'])) {
            $name = is_array($data['name']) ? $data['name']['en'] ?? reset($data['name']) : $data['name'];
            $data['slug'] = Str::slug($name);
        }
        return $this->categoryRepository->update($id, $data);
    }

    public function deleteCategory($id)
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            throw new NotFoundHttpException(__('advertisement::messages.advertisement_categories.not_found'));
        }
        if ($this->categoryRepository->hasAdvertisements($id)) {
            throw new HttpException(409, __('advertisement::messages.advertisement_categories.cannot_delete_has_ads'));
        }
        $this->categoryRepository->delete($id);
    }

    public function toggleCategoryStatus($id)
    {
        $category = $this->categoryRepository->toggleStatus($id);
        if (!$category) {
            throw new NotFoundHttpException(__('advertisement::messages.advertisement_categories.not_found'));
        }
        return $category;
    }

    public function getActiveCategories($filters = [])
    {
        return $this->categoryRepository->getActive($filters);
    }

    public function getCategoriesWithSubcategories($filters = [])
    {
        return $this->categoryRepository->getWithSubcategories($filters);
    }
}
