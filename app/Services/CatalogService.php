<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CatalogService
{
    public function __construct(private CategoryService $categoryService)
    {
    }

    public function categories(): Collection
    {
        return Category::query()
            ->whereHas('products', fn ($query) => $query->where('active', true))
            ->orderBy('name')
            ->get();
    }

    public function categorySummary(): Collection
    {
        return Category::query()
            ->whereHas('products', fn ($query) => $query->where('active', true))
            ->withCount([
                'products' => fn ($query) => $query->where('active', true),
            ])
            ->orderBy('name')
            ->get();
    }

    public function paginatedProducts(?string $search = null, ?string $category = null): LengthAwarePaginator
    {
        $resolvedCategory = $this->categoryService->findByFilter($category);

        return Product::with(['primaryImage', 'category'])
            ->where('active', true)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category, function ($query) use ($resolvedCategory) {
                if (! $resolvedCategory) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereBelongsTo($resolvedCategory, 'category');
            })
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();
    }
}
