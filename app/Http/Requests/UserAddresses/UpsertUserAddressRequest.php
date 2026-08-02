<?php

namespace App\Http\Requests\UserAddresses;

use Illuminate\Foundation\Http\FormRequest;

class UpsertUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'primary_address' => ['required', 'string', 'max:255'],
            'secondary_address' => ['nullable', 'string', 'max:255'],
            'shipping_city_id' => ['required', 'integer', 'exists:shipping_cities,id'],
        ];
    }

    public function addressData(): array
    {
        $validated = $this->validated();

        return [
            'primary_address' => trim((string) $validated['primary_address']),
            'secondary_address' => filled($validated['secondary_address'] ?? null)
                ? trim((string) $validated['secondary_address'])
                : null,
            'shipping_city_id' => (int) $validated['shipping_city_id'],
        ];
    }
}
