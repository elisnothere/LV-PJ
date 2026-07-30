<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $categories = Product::query()
            ->where('active', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $products = Product::query()
            ->where('active', true)
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $search = (string) $request->string('buscar');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('categoria'), fn ($query) => $query->where('category', $request->categoria))
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('catalog.index', compact('categories', 'products'));
    }
}
