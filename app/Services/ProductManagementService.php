<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductManagementService
{
    public function __construct(private ProductImageService $productImageService)
    {
    }

    public function create(array $productData, array $imageFiles, string $imageUrlsInput): Product
    {
        return DB::transaction(function () use ($productData, $imageFiles, $imageUrlsInput) {
            $product = Product::create($productData);

            $this->productImageService->attachNewImages($product, $imageFiles, $imageUrlsInput);
            $this->productImageService->ensurePrimaryImage($product);

            return $product;
        });
    }

    public function update(Product $product, array $productData, array $imageFiles, string $imageUrlsInput, array $deleteImageIds, ?int $primaryImageId): Product
    {
        return DB::transaction(function () use ($product, $productData, $imageFiles, $imageUrlsInput, $deleteImageIds, $primaryImageId) {
            $product->update($productData);

            $this->productImageService->deleteSelectedImages($product, $deleteImageIds);
            $this->productImageService->attachNewImages($product, $imageFiles, $imageUrlsInput);
            $this->productImageService->applyRequestedPrimaryImage($product, $primaryImageId);
            $this->productImageService->ensurePrimaryImage($product);

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        $this->productImageService->deleteProductImages($product);
        $product->delete();
    }
}
