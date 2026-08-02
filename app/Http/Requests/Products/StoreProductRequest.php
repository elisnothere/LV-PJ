<?php

namespace App\Http\Requests\Products;

use App\Http\Requests\Products\Concerns\InteractsWithProductForm;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use InteractsWithProductForm;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'promotional_price' => ['nullable', 'numeric', 'min:0'],
            'promotional_starts_at' => ['nullable', 'date'],
            'promotional_ends_at' => ['nullable', 'date'],
            'stock' => ['required', 'integer', 'min:0'],
        ], $this->categoryRules(), $this->imageUrlRules());
    }
}
