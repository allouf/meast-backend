<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Store\Models\Category;
use Modules\Store\Models\OrderItem;
use Modules\Store\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryRepository implements CategoryRepositoryInterface
{
    use PaginateTrait;
    protected $category;

    public function __construct(Category $category)
    {
        $this->category = $category;
    }



    public function all($search = null, $paginate = true)
    {
        $query = $this->category->whereIsRoot()->defaultOrder()->with('allChildren');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name->en', 'like', "%{$search}%")
                    ->orWhere('name->ar', 'like', "%{$search}%");
            });
        }

        if ($paginate) {
            return $this->paginate($query);
        }

        return [
            'data' => $query->get(),
            'meta' => null
        ];
    }

    public function getActive(?string $search = null, ?string $sort = null, bool $paginate = true)
    {
        $query = $this->category
            ->where('is_active', true)
            ->whereIsRoot()
            ->with('allChildren');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name->ar', 'like', "%{$search}%")
                    ->orWhere('name->en', 'like', "%{$search}%");
            });
        }

        if ($sort !== 'most_popular') {
            match ($sort) {
                'newest' => $query->reorder()->latest(),
                default  => $query->defaultOrder(),
            };

            if ($paginate) {
                return $this->paginate($query);
            }

            $data = $query->get();

            return [
                'data' => $data,
                'meta' => null
            ];
        }

        // ✅ most_popular logic (tree-aware)
        $categories = $query->get();

        $categories->each(function ($category) {
            $categoryIds = $category
                ->descendants()
                ->pluck('id')
                ->push($category->id)
                ->toArray();


            $category->order_items_count = OrderItem::forCategoryIds($categoryIds)->count();
        });

        $sorted = $categories
            ->sortByDesc('order_items_count')
            ->values();

        if (!$paginate) {
            return [
                'data' => $sorted,
                'meta' => null
            ];
        }

        // ✅ Pagination يدوي
        $perPage = request()->get('per_page', 10);
        $page    = LengthAwarePaginator::resolveCurrentPage();

        $paginator = new LengthAwarePaginator(
            $sorted->slice(($page - 1) * $perPage, $perPage),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return [
            'data' => collect($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ]
        ];
    }

    public function getWithChildrens(?string $search = null)
    {
        $baseQuery = $this->category
            ->where('is_active', true)
            ->defaultOrder();

        if (!$search) {
            return $baseQuery->get()->toTree();
        }

        $matched = $baseQuery
            ->where(function ($q) use ($search) {
                $q->where('name->ar', 'like', "%{$search}%")
                    ->orWhere('name->en', 'like', "%{$search}%");
            })
            ->get();

        $ids = $matched
            ->flatMap(function ($category) {
                return collect()
                    ->merge($category->ancestors()->pluck('id'))
                    ->merge([$category->id])
                    ->merge($category->descendants()->pluck('id'));
            })
            ->unique()
            ->values();

        return $this->category
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->defaultOrder()
            ->get()
            ->toTree();
    }


    public function findById($id)
    {
        return $this->category->with('children')->findOrFail($id);
    }
    public function create(array $data)
    {
        return $this->category->create($data);
    }

    public function update(array $data, $id)
    {
        $record = $this->findById($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        $record = $this->findById($id);
        return $record->delete();
    }
    public function switchActivation($id)
    {
        $category = $this->findById($id);
        $category->is_active = !$category->is_active;
        $category->save();
    }
    public function getMaxSortOrderByParent($parentId)
    {
        return $this->category->where('parent_id', $parentId)->max('sort_order') ?? 0;
    }
    public function getSiblings($parentId)
    {
        return $this->category->where('parent_id', $parentId)->orderBy('sort_order')->get();
    }

    public function updateSortOrder($id, $sortOrder)
    {
        return $this->findById($id)->update(['sort_order' => $sortOrder]);
    }
}
