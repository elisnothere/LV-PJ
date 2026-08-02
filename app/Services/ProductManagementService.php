<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductManagementService
{
    public function __construct(
        private ProductImageService $productImageService,
        private CategoryService $categoryService,
        private StockSubscriptionService $stockSubscriptionService,
    ) {
    }

    public function create(array $productData, ?int $categoryId, ?string $newCategoryName, array $imageFiles, string $imageUrlsInput): Product
    {
        return DB::transaction(function () use ($productData, $categoryId, $newCategoryName, $imageFiles, $imageUrlsInput) {
            $category = $this->categoryService->resolveFromSelection($categoryId, $newCategoryName);
            $product = Product::create([
                ...$productData,
                'category_id' => $category->id,
            ]);

            $this->productImageService->attachNewImages($product, $imageFiles, $imageUrlsInput);
            $this->productImageService->ensurePrimaryImage($product);

            return $product;
        });
    }

    public function update(Product $product, array $productData, ?int $categoryId, ?string $newCategoryName, array $imageFiles, string $imageUrlsInput, array $deleteImageIds, ?int $primaryImageId): Product
    {
        $previousStock = (int) $product->stock;

        $updatedProduct = DB::transaction(function () use ($product, $productData, $categoryId, $newCategoryName, $imageFiles, $imageUrlsInput, $deleteImageIds, $primaryImageId) {
            $category = $this->categoryService->resolveFromSelection($categoryId, $newCategoryName);

            $product->update([
                ...$productData,
                'category_id' => $category->id,
            ]);

            $this->productImageService->deleteSelectedImages($product, $deleteImageIds);
            $this->productImageService->attachNewImages($product, $imageFiles, $imageUrlsInput);
            $this->productImageService->applyRequestedPrimaryImage($product, $primaryImageId);
            $this->productImageService->ensurePrimaryImage($product);

            return $product->fresh(['category', 'primaryImage', 'images']);
        });

        $this->stockSubscriptionService->notifyIfBackInStock(
            $updatedProduct,
            $previousStock,
            (int) $updatedProduct->stock,
        );

        return $updatedProduct;
    }

    public function delete(Product $product): void
    {
        $this->productImageService->deleteProductImages($product);
        $product->delete();
    }
}
