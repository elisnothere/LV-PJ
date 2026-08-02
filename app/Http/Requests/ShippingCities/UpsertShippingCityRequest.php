<?php

namespace App\Http\Requests\ShippingCities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertShippingCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shippingCity = $this->route('shippingCity');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('shipping_cities', 'name')->ignore($shippingCity?->id),
            ],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function shippingCityData(): array
    {
        $validated = $this->validated();

        return [
            'name' => trim((string) $validated['name']),
            'shipping_cost' => $validated['shipping_cost'],
            'active' => $this->boolean('active', true),
        ];
    }
}
