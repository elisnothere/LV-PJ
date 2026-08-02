<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'shipping_city_id' => ['required', 'integer', 'exists:shipping_cities,id'],
        ];
    }

    public function orderData(): array
    {
        return collect($this->validated())
            ->except('shipping_city_id')
            ->all();
    }

    public function shippingCityId(): int
    {
        return (int) $this->validated('shipping_city_id');
    }
}
