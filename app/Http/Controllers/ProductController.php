<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('primaryImage')->latest()->paginate(10);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $product = Product::create($this->productData($request));

            $this->storeNewImages($product, $request);
            $this->ensurePrimaryImage($product);
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'primaryImage']);

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($this->productData($request));

            $this->deleteSelectedImages($product, $request);
            $this->storeNewImages($product, $request);
            $this->applyRequestedPrimaryImage($product, $request);
            $this->ensurePrimaryImage($product);
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        $product->load('images');

        foreach ($product->images as $image) {
            $this->deleteLocalImageFile($image->image_url);
        }

        if ($product->image_url && $product->images->isEmpty()) {
            $this->deleteLocalImageFile($product->image_url);
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

    private function productData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image_files' => ['nullable', 'array'],
            'image_files.*' => ['uploaded', 'image', 'max:10240'],
            'image_urls' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    foreach ($this->splitImageUrls((string) $value) as $imageUrl) {
                        if (strlen($imageUrl) > 255 || ! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                            $fail('Ingrese una URL de imagen valida por linea.');
                            return;
                        }
                    }
                },
            ],
            'primary_image_id' => ['nullable', 'integer'],
            'delete_image_ids' => ['nullable', 'array'],
            'delete_image_ids.*' => ['integer'],
        ], [
            'image_files.*.uploaded' => 'No se pudo subir una imagen. Verifique que el archivo pese menos de 10 MB y vuelva a intentar.',
            'image_files.*.image' => 'Cada archivo subido debe ser una imagen valida.',
            'image_files.*.max' => 'Cada imagen debe pesar como maximo 10 MB.',
        ]);

        return [
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'active' => $request->boolean('active'),
        ];
    }

    private function storeNewImages(Product $product, Request $request): void
    {
        $sortOrder = ((int) $product->images()->max('sort_order')) + 1;

        foreach ($request->file('image_files', []) as $file) {
            $product->images()->create([
                'image_url' => Storage::url($file->store('productos', 'public')),
                'source' => 'upload',
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($this->submittedImageUrls($request) as $imageUrl) {
            $product->images()->create([
                'image_url' => $imageUrl,
                'source' => 'url',
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function submittedImageUrls(Request $request): array
    {
        return collect($this->splitImageUrls((string) $request->input('image_urls')))
            ->unique()
            ->values()
            ->all();
    }

    private function splitImageUrls(string $value): array
    {
        return collect(preg_split('/\R/', $value))
            ->map(fn ($url) => trim($url))
            ->filter()
            ->values()
            ->all();
    }
    private function deleteSelectedImages(Product $product, Request $request): void
    {
        $imageIds = $request->input('delete_image_ids', []);

        if (empty($imageIds)) {
            return;
        }

        $images = $product->images()
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            $this->deleteLocalImageFile($image->image_url);
            $image->delete();
        }
    }

    private function applyRequestedPrimaryImage(Product $product, Request $request): void
    {
        $primaryImageId = $request->integer('primary_image_id');

        if (! $primaryImageId) {
            return;
        }

        $primaryImage = $product->images()->whereKey($primaryImageId)->first();

        if (! $primaryImage) {
            return;
        }

        $product->images()->update(['is_primary' => false]);
        $primaryImage->forceFill(['is_primary' => true])->save();
    }

    private function ensurePrimaryImage(Product $product): void
    {
        $primaryImage = $product->images()
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $primaryImage) {
            $primaryImage = $product->images()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if ($primaryImage) {
                $primaryImage->forceFill(['is_primary' => true])->save();
            }
        }

        $product->forceFill([
            'image_url' => $primaryImage?->image_url,
        ])->save();
    }

    private function deleteLocalImageFile(?string $imageUrl): void
    {
        if ($imageUrl && str_starts_with($imageUrl, '/storage/productos/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $imageUrl));
        }
    }
}
