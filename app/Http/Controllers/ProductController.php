<?php

namespace App\Http\Controllers;

use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductImageService;
use App\Services\ProductManagementService;

class ProductController extends Controller
{
    public function __construct(
        private ProductManagementService $productManagementService,
        private ProductImageService $productImageService,
    ) {
    }

    public function index()
    {
        $products = Product::with('primaryImage')->latest()->paginate(10);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(StoreProductRequest $request)
    {
        if ($uploadErrors = $this->productImageService->validateUploads($request->imageFilesPayload())) {
            return back()->withErrors($uploadErrors)->withInput();
        }

        $this->productManagementService->create(
            $request->productData(),
            $request->imageFilesPayload(),
            $request->imageUrlsInput(),
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'primaryImage']);

        return view('products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        if ($uploadErrors = $this->productImageService->validateUploads($request->imageFilesPayload())) {
            return back()->withErrors($uploadErrors)->withInput();
        }

        $this->productManagementService->update(
            $product,
            $request->productData(),
            $request->imageFilesPayload(),
            $request->imageUrlsInput(),
            $request->deleteImageIds(),
            $request->primaryImageId(),
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        $this->productManagementService->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
