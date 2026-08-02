<?php

namespace App\Services;

use App\Models\ShippingCity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ShippingCityService
{
    public function paginated(?string $search = null): LengthAwarePaginator
    {
        return ShippingCity::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
    }

    public function active(): Collection
    {
        return ShippingCity::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): ShippingCity
    {
        return ShippingCity::create($data);
    }

    public function update(ShippingCity $shippingCity, array $data): void
    {
        $shippingCity->update($data);
    }

    public function toggleActive(ShippingCity $shippingCity): void
    {
        $shippingCity->update(['active' => ! $shippingCity->active]);
    }

    public function findActiveOrNull(?int $shippingCityId): ?ShippingCity
    {
        if (! $shippingCityId) {
            return null;
        }

        return ShippingCity::query()
            ->whereKey($shippingCityId)
            ->where('active', true)
            ->first();
    }

    public function resolveActiveOrFail(?int $shippingCityId): ShippingCity
    {
        $shippingCity = $this->findActiveOrNull($shippingCityId);

        if (! $shippingCity) {
            throw ValidationException::withMessages([
                'shipping_city_id' => 'Seleccione una ciudad de envio valida.',
            ]);
        }

        return $shippingCity;
    }
}
