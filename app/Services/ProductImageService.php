<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;

class ProductImageService
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function validateUploads(array $files): ?MessageBag
    {
        $errors = new MessageBag();

        foreach ($files as $index => $file) {
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
     * @param  array<int, UploadedFile>  $imageFiles
     */
    public function attachNewImages(Product $product, array $imageFiles, string $imageUrlsInput): void
    {
        $sortOrder = ((int) $product->images()->max('sort_order')) + 1;

        foreach ($imageFiles as $file) {
            $product->images()->create([
                'image_url' => Storage::url($file->store('productos', 'public')),
                'source' => 'upload',
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($this->submittedImageUrls($imageUrlsInput) as $imageUrl) {
            $product->images()->create([
                'image_url' => $imageUrl,
                'source' => 'url',
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $imageIds
     */
    public function deleteSelectedImages(Product $product, array $imageIds): void
    {
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

    public function applyRequestedPrimaryImage(Product $product, ?int $primaryImageId): void
    {
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

    public function ensurePrimaryImage(Product $product): void
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

    public function deleteProductImages(Product $product): void
    {
        $product->load('images');

        foreach ($product->images as $image) {
            $this->deleteLocalImageFile($image->image_url);
        }

        if ($product->image_url && $product->images->isEmpty()) {
            $this->deleteLocalImageFile($product->image_url);
        }
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

    /**
     * @return array<int, string>
     */
    private function submittedImageUrls(string $value): array
    {
        return collect(preg_split('/\R/', $value))
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function deleteLocalImageFile(?string $imageUrl): void
    {
        if ($imageUrl && str_starts_with($imageUrl, '/storage/productos/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $imageUrl));
        }
    }
}
