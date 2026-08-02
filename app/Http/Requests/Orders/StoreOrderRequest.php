<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usingSavedAddress = $this->filled('user_address_id');

        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'delivery_address' => [Rule::requiredIf(! $usingSavedAddress), 'string', 'max:500'],
            'shipping_city_id' => [Rule::requiredIf(! $usingSavedAddress), 'nullable', 'integer', 'exists:shipping_cities,id'],
            'user_address_id' => ['nullable', 'integer', 'exists:user_addresses,id'],
        ];
    }

    public function orderData(): array
    {
        return collect($this->validated())
            ->except(['shipping_city_id', 'user_address_id'])
            ->all();
    }

    public function shippingCityId(): ?int
    {
        $value = $this->validated('shipping_city_id');

        return is_numeric($value) ? (int) $value : null;
    }

    public function userAddressId(): ?int
    {
        $value = $this->validated('user_address_id');

        return is_numeric($value) ? (int) $value : null;
    }
}
