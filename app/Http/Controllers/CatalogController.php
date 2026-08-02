<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalogService)
    {
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
}
