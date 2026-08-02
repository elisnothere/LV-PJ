<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;

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
        if ($uploadErrors = $this->validateImageUploads($request)) {
            return back()->withErrors($uploadErrors)->withInput();
        }

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
        if ($uploadErrors = $this->validateImageUploads($request)) {
            return back()->withErrors($uploadErrors)->withInput();
        }

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
            'image_files.*' => ['image', 'max:10240'],
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

    private function validateImageUploads(Request $request): ?MessageBag
    {
        $errors = new MessageBag();

        foreach ($this->normalizedImageFiles($request) as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            if ($file->isValid()) {
                continue;
            }

            $errors->add("image_files.$index", $this->uploadErrorMessage($file->getError()));
            $this->logUploadFailure($file, $index);
        }

        return $errors->isEmpty() ? null : $errors;
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function normalizedImageFiles(Request $request): array
    {
        $files = $request->allFiles()['image_files'] ?? [];

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return collect($files)
            ->flatten(1)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->values()
            ->all();
    }

    private function uploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen excede el limite permitido por el servidor o el formulario.',
            UPLOAD_ERR_PARTIAL => 'La imagen se subio de forma incompleta. Vuelva a intentarlo.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no encontro la carpeta temporal de subida.',
            UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir la imagen en disco.',
            UPLOAD_ERR_EXTENSION => 'Una extension del servidor interrumpio la subida de la imagen.',
            default => 'No se pudo subir una imagen. Vuelva a intentarlo.',
        };
    }

    private function logUploadFailure(UploadedFile $file, int $index): void
    {
        Log::warning('Product image upload failed', [
            'field' => "image_files.$index",
            'client_name' => $file->getClientOriginalName(),
            'client_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'php_upload_error' => $file->getError(),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_tmp_dir' => ini_get('upload_tmp_dir'),
        ]);
    }

    private function storeNewImages(Product $product, Request $request): void
    {
        $sortOrder = ((int) $product->images()->max('sort_order')) + 1;

        foreach ($this->normalizedImageFiles($request) as $file) {
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
