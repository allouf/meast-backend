<?php

namespace Modules\Advertisement\Services;

use Illuminate\Support\Str;
use Modules\Advertisement\Models\AdvertisementSubcategory;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementSubcategoryRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertisementSubcategoryService
{
    public function __construct(
        protected AdvertisementSubcategoryRepositoryInterface $subcategoryRepository
    ) {}

    public function getAllSubcategories($filters = [])
    {
        return $this->subcategoryRepository->all($filters);
    }

    public function getSubcategoryById($id)
    {
        return $this->subcategoryRepository->find($id);
    }

    public function getSubcategoryBySlug($slug)
    {
        return $this->subcategoryRepository->findBySlug($slug);
    }

    public function createSubcategory($data)
    {
        if (!isset($data['slug']) && isset($data['name'])) {
            $name = is_array($data['name']) ? $data['name']['en'] ?? reset($data['name']) : $data['name'];
            $data['slug'] = Str::slug($name);
        }
        return $this->subcategoryRepository->create($data);
    }

    public function updateSubcategory($id, $data)
    {
        $subcategory = $this->subcategoryRepository->find($id);
        if (!$subcategory) {
            return null;
        }
        if (isset($data['name'])) {
            $name = is_array($data['name']) ? $data['name']['en'] ?? reset($data['name']) : $data['name'];
            $data['slug'] = Str::slug($name);
        }
        return $this->subcategoryRepository->update($id, $data);
    }

    public function deleteSubcategory($id)
    {
        $subcategory = $this->subcategoryRepository->find($id);
        if (!$subcategory) {
            throw new NotFoundHttpException(__('advertisement::messages.advertisement_subcategories.not_found'));
        }
        if ($this->subcategoryRepository->hasAdvertisements($id)) {
            throw new HttpException(409, __('advertisement::messages.advertisement_subcategories.cannot_delete_has_ads'));
        }
        $this->subcategoryRepository->delete($id);
        return true;
    }

    public function toggleStatus(int $id): AdvertisementSubcategory
    {
        $subcategory = $this->subcategoryRepository->toggleStatus($id);
        if (!$subcategory) {
            throw new NotFoundHttpException(__('advertisement::messages.advertisement_subcategories.not_found'));
        }
        return $subcategory;
    }

    public function getSubcategoriesByCategory($categoryId)
    {
        return $this->subcategoryRepository->getByCategory($categoryId);
    }

    public function getActiveSubcategories($filters = [])
    {
        return $this->subcategoryRepository->getActive($filters);
    }
}
