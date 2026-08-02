<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CatalogService
{
    public function categories(): Collection
    {
        return Product::query()
            ->where('active', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    public function categorySummary(): Collection
    {
        return Product::query()
            ->where('active', true)
            ->selectRaw('category, count(*) as products_count')
            ->groupBy('category')
            ->orderBy('category')
            ->get();
    }

    public function paginatedProducts(?string $search = null, ?string $category = null): LengthAwarePaginator
    {
        return Product::with('primaryImage')
            ->where('active', true)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();
    }
}
