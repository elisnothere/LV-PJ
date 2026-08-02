<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShippingCity;
use DomainException;
use Illuminate\Session\Store;

class CartService
{
    private const SHIPPING_CITY_SESSION_KEY = 'checkout.shipping_city_id';

    public function __construct(
        private Store $session,
        private ShippingCityService $shippingCityService,
    ) {
    }

    public function contents(): array
    {
        $cart = $this->session->get('cart', []);

        if ($cart === []) {
            return [];
        }

        $products = Product::with('primaryImage')
            ->whereKey(array_keys($cart))
            ->get()
            ->keyBy('id');

        $syncedCart = collect($cart)
            ->map(function (array $item, int|string $productId) use ($products) {
                $product = $products->get((int) $productId);

                if (! $product) {
                    return null;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->primary_image_url,
                    'price' => $product->effectivePrice(),
                    'regular_price' => (float) $product->price,
                    'quantity' => min((int) $item['quantity'], max((int) $product->stock, 1)),
                    'stock' => (int) $product->stock,
                ];
            })
            ->filter()
            ->mapWithKeys(fn (?array $item) => $item ? [$item['id'] => $item] : [])
            ->all();

        $this->session->put('cart', $syncedCart);

        return $syncedCart;
    }

    public function subtotal(): float
    {
        return (float) collect($this->contents())->sum(fn ($item) => $item['price'] * $item['quantity']);
    }

    public function total(): float
    {
        return $this->subtotal();
    }

    public function selectedShippingCityId(): ?int
    {
        $value = $this->session->get(self::SHIPPING_CITY_SESSION_KEY);

        return is_numeric($value) ? (int) $value : null;
    }

    public function setShippingCityId(?int $shippingCityId): void
    {
        if ($shippingCityId) {
            $this->session->put(self::SHIPPING_CITY_SESSION_KEY, $shippingCityId);

            return;
        }

        $this->session->forget(self::SHIPPING_CITY_SESSION_KEY);
    }

    public function selectedShippingCity(?int $shippingCityId = null): ?ShippingCity
    {
        $shippingCityId ??= $this->selectedShippingCityId();

        return $this->shippingCityService->findActiveOrNull($shippingCityId);
    }

    public function shippingCost(?int $shippingCityId = null): float
    {
        return (float) ($this->selectedShippingCity($shippingCityId)?->shipping_cost ?? 0);
    }

    public function totalWithShipping(?int $shippingCityId = null): float
    {
        return $this->subtotal() + $this->shippingCost($shippingCityId);
    }

    public function add(Product $product, int $quantity): void
    {
        if (! $product->active || $product->stock < 1) {
            throw new DomainException('El producto no esta disponible.');
        }

        $cart = $this->contents();
        $currentQuantity = $cart[$product->id]['quantity'] ?? 0;
        $newQuantity = min($currentQuantity + $quantity, $product->stock);

        $cart[$product->id] = [
            'id' => $product->id,
            'name' => $product->name,
            'image_url' => $product->primary_image_url,
            'price' => $product->effectivePrice(),
            'regular_price' => (float) $product->price,
            'quantity' => $newQuantity,
            'stock' => $product->stock,
        ];

        $this->session->put('cart', $cart);
    }

    public function update(Product $product, int $quantity): void
    {
        $cart = $this->contents();

        if (! isset($cart[$product->id])) {
            return;
        }

        $cart[$product->id]['quantity'] = $quantity;
        $this->session->put('cart', $cart);
    }

    public function remove(Product $product): void
    {
        $cart = $this->contents();
        unset($cart[$product->id]);

        $this->session->put('cart', $cart);
    }

    public function clear(): void
    {
        $this->session->forget('cart');
        $this->session->forget(self::SHIPPING_CITY_SESSION_KEY);
    }
}
