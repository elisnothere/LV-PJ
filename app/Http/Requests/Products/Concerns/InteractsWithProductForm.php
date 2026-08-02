<?php

namespace App\Http\Requests\Products\Concerns;

use Illuminate\Http\UploadedFile;

trait InteractsWithProductForm
{
    public function productData(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'active' => $this->boolean('active'),
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function imageFilesPayload(): array
    {
        $files = $this->allFiles()['image_files'] ?? [];

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

    public function imageUrlsInput(): string
    {
        return (string) $this->input('image_urls', '');
    }

    /**
     * @return array<int, int>
     */
    public function deleteImageIds(): array
    {
        return collect($this->input('delete_image_ids', []))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    }

    public function primaryImageId(): ?int
    {
        $value = $this->input('primary_image_id');

        return $value !== null && $value !== '' ? (int) $value : null;
    }

    protected function imageUrlRules(): array
    {
        return [
            'image_files' => ['nullable', 'array'],
            'image_files.*' => ['image', 'max:10240'],
            'image_urls' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                foreach ($this->splitImageUrls((string) $value) as $imageUrl) {
                    if (strlen($imageUrl) > 255 || ! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $fail('Ingrese una URL de imagen valida por linea.');

                        return;
                    }
                }
            }],
            'primary_image_id' => ['nullable', 'integer'],
            'delete_image_ids' => ['nullable', 'array'],
            'delete_image_ids.*' => ['integer'],
        ];
    }

    protected function splitImageUrls(string $value): array
    {
        return collect(preg_split('/\R/', $value))
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->values()
            ->all();
    }

    public function messages(): array
    {
        return [
            'image_files.*.image' => 'Cada archivo subido debe ser una imagen valida.',
            'image_files.*.max' => 'Cada imagen debe pesar como maximo 10 MB.',
        ];
    }
}
