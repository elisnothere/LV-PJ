<?php

namespace App\Services;

use App\Models\Category;
use DomainException;
use Illuminate\Support\Collection;

class CategoryService
{
    public function all(): Collection
    {
        return Category::query()
            ->orderBy('name')
            ->get();
    }

    public function create(string $name): Category
    {
        return $this->createOrRename(null, $name);
    }

    public function update(Category $category, string $name): Category
    {
        return $this->createOrRename($category, $name);
    }

    public function resolveByName(string $name): Category
    {
        $normalizedName = trim($name);
        $slug = Category::makeSlug($normalizedName);

        return Category::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $normalizedName !== '' ? $normalizedName : 'Sin categoria'],
        );
    }

    public function resolveFromSelection(?int $categoryId, ?string $newCategoryName): Category
    {
        $newCategoryName = trim((string) $newCategoryName);

        if ($newCategoryName !== '') {
            return $this->resolveByName($newCategoryName);
        }

        return Category::query()->findOrFail($categoryId);
    }

    public function findByFilter(?string $value): ?Category
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Category::query()
            ->where('slug', Category::makeSlug($value))
            ->orWhere('name', $value)
            ->first();
    }

    private function createOrRename(?Category $category, string $name): Category
    {
        $normalizedName = trim($name);
        $slug = Category::makeSlug($normalizedName);
        $displayName = $normalizedName !== '' ? $normalizedName : 'Sin categoria';

        $existing = Category::query()->where('slug', $slug)->first();

        if (! $category) {
            return $existing ?? Category::query()->create([
                'name' => $displayName,
                'slug' => $slug,
            ]);
        }

        if ($existing && ! $existing->is($category)) {
            if ($category->products()->exists()) {
                $category->products()->update(['category_id' => $existing->id]);
            }

            $category->delete();

            return $existing;
        }

        $category->update([
            'name' => $displayName,
            'slug' => $slug,
        ]);

        return $category->fresh();
    }
}
