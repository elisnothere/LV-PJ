<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\UpsertCategoryRequest;
use App\Models\Category;
use App\Services\CatalogService;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        private CatalogService $catalogService,
        private CategoryService $categoryService,
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
