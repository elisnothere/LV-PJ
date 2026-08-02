<?php

namespace App\Services;

use App\Models\Product;
use DomainException;
use Illuminate\Session\Store;

class CartService
{
    public function __construct(private Store $session)
    {
    }

    public function contents(): array
    {
        return $this->session->get('cart', []);
    }

    public function total(): float
    {
        return (float) collect($this->contents())->sum(fn ($item) => $item['price'] * $item['quantity']);
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
            'price' => (float) $product->price,
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
    }
}
