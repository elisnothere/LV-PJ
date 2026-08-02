<?php

namespace App\Http\Requests\Cart;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class CartQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:' . max($product->stock, 1)],
        ];
    }

    public function quantity(): int
    {
        return (int) $this->validated('quantity');
    }
}
