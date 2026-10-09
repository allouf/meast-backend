<?php
namespace App\Services;

use Modules\Core\Models\User;
use Modules\Store\Models\Category;

class StatisticsService
{
    public function getStatistics(array $filters): array
    {
        $contentFilters = $this->contentFilters($filters);
        $userFilters = $this->userFilters($filters);

        return [
            'users' => User::where('type', 'user')->where($userFilters)->count(),
            'companies' => User::where('type', 'company')->where($userFilters)->count(),
        ];
    }

    public function getAnalyticsByCategory(): array
    {
        $parents = Category::whereNull('parent_id')->get();

        return $parents->map(function ($parent) {
            $categoryIds = $parent->descendantsAndSelf($parent->id)->pluck('id');

            return [
                'category_en' => $parent->getTranslation('name', 'en'),
                'category_ar' => $parent->getTranslation('name', 'ar'),
            ];
        })->toArray();
    }

    private function contentFilters(array $filters): \Closure
    {
        return function ($query) use ($filters) {
            if (!empty($filters['country_code'])) {
                $query->where('country_code', $filters['country_code']);
            }
            if (!empty($filters['region'])) {
                $query->where('region', 'LIKE', '%' . $filters['region'] . '%');
            }

            // فلترة عبر شجرة الفئات
            if (!empty($filters['category_id'])) {
                $category = Category::find($filters['category_id']);
                if ($category) {
                    $categoryIds = $category->descendantsAndSelf($category->id)->pluck('id');
                    $query->whereIn('category_id', $categoryIds);
                }
            }

            if (!empty($filters['from_date'])) {
                $query->whereDate('created_at', '>=', $filters['from_date']);
            }

            if (!empty($filters['to_date'])) {
                $query->whereDate('created_at', '<=', $filters['to_date']);
            }
        };
    }


    private function userFilters(array $filters): \Closure
    {
        return function ($query) use ($filters) {
            if (!empty($filters['country_code'])) {
                $query->where('country_code', $filters['country_code']);
            }
            if (!empty($filters['region'])) {
                $query->where('region', 'LIKE', '%' . $filters['region'] . '%');
            }
            if (!empty($filters['from_date'])) {
                $query->whereDate('created_at', '>=', $filters['from_date']);
            }
            if (!empty($filters['to_date'])) {
                $query->whereDate('created_at', '<=', $filters['to_date']);
            }
        };
    }
}
