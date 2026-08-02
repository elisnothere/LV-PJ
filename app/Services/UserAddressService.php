<?php

namespace App\Services;

use App\Models\ShippingCity;
use App\Models\UserAddress;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserAddressService
{
    public function __construct(private ShippingCityService $shippingCityService)
    {
    }

    public function forUser(int $userId): Collection
    {
        return UserAddress::query()
            ->with('shippingCity')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function createForUser(int $userId, array $data): UserAddress
    {
        $shippingCity = $this->shippingCityService->resolveActiveOrFail((int) $data['shipping_city_id']);

        return UserAddress::create([
            'user_id' => $userId,
            'shipping_city_id' => $shippingCity->id,
            'primary_address' => $data['primary_address'],
            'secondary_address' => $data['secondary_address'],
        ]);
    }

    public function updateForUser(UserAddress $userAddress, int $userId, array $data): void
    {
        $userAddress = $this->loadOwnedOrFail($userAddress, $userId);
        $shippingCity = $this->shippingCityService->resolveActiveOrFail((int) $data['shipping_city_id']);

        $userAddress->update([
            'shipping_city_id' => $shippingCity->id,
            'primary_address' => $data['primary_address'],
            'secondary_address' => $data['secondary_address'],
        ]);
    }

    public function deleteForUser(UserAddress $userAddress, int $userId): void
    {
        $this->loadOwnedOrFail($userAddress, $userId)->delete();
    }

    public function loadOwnedOrFail(UserAddress $userAddress, int $userId): UserAddress
    {
        if ($userAddress->user_id !== $userId) {
            throw new AuthorizationException();
        }

        $userAddress->loadMissing('shippingCity');

        return $userAddress;
    }

    public function findOwnedActiveOrNull(?int $userAddressId, ?int $userId): ?UserAddress
    {
        if (! $userAddressId || ! $userId) {
            return null;
        }

        return UserAddress::query()
            ->with('shippingCity')
            ->whereKey($userAddressId)
            ->where('user_id', $userId)
            ->whereHas('shippingCity', fn ($query) => $query->where('active', true))
            ->first();
    }

    public function resolveOwnedActiveOrFail(?int $userAddressId, ?int $userId): UserAddress
    {
        $userAddress = $this->findOwnedActiveOrNull($userAddressId, $userId);

        if (! $userAddress) {
            throw ValidationException::withMessages([
                'user_address_id' => 'Seleccione una direccion de entrega valida.',
            ]);
        }

        return $userAddress;
    }

    public function shippingCityFor(UserAddress $userAddress): ShippingCity
    {
        return $userAddress->shippingCity;
    }
}
