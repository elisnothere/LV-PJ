<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\UpsertCategoryRequest;
use App\Http\Requests\Products\StoreProductStockSubscriptionRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogService;
use App\Services\CategoryService;
use App\Services\StockSubscriptionService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        private CatalogService $catalogService,
        private CategoryService $categoryService,
        private StockSubscriptionService $stockSubscriptionService,
    ) {
    }

    public function index(Request $request)
    {
        $search = $request->filled('buscar') ? (string) $request->string('buscar') : null;
        $category = $request->filled('categoria') ? (string) $request->string('categoria') : null;

        return view('catalog.index', [
            'categories' => $this->catalogService->categories(),
            'products' => $this->catalogService->paginatedProducts($search, $category),
        ]);
    }

    public function show(Product $product)
    {
        return view('catalog.show', [
            'product' => $this->catalogService->publicProduct($product),
        ]);
    }

    public function subscribe(StoreProductStockSubscriptionRequest $request, Product $product)
    {
        $product = $this->catalogService->publicProduct($product);
        $result = $this->stockSubscriptionService->subscribe($product, $request->email());

        return redirect()
            ->route('catalog.show', $product)
            ->with($result['created'] || $result['duplicate'] ? 'success' : 'error', $result['message']);
    }

    public function categories()
    {
        return view('partials.categoria', [
            'categories' => $this->catalogService->categorySummary(),
        ]);
    }

    public function storeCategory(UpsertCategoryRequest $request)
    {
        $this->categoryService->create($request->categoryName());

        return redirect()
            ->to('/categoria')
            ->with('success', 'Categoria creada correctamente.');
    }

    public function updateCategory(UpsertCategoryRequest $request, Category $category)
    {
        $this->categoryService->update($category, $request->categoryName());

        return redirect()
            ->to('/categoria')
            ->with('success', 'Categoria actualizada correctamente.');
    }
}
