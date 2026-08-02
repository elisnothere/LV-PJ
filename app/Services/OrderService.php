<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createFromCart(array $cart, array $orderData, ?int $userId): Order
    {
        if (empty($cart)) {
            throw new DomainException('El carrito esta vacio.');
        }

        return DB::transaction(function () use ($cart, $orderData, $userId) {
            $order = Order::create([
                ...$orderData,
                'user_id' => $userId,
                'code' => 'PED-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'total' => collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']),
            ]);

            foreach ($cart as $item) {
                $product = Product::lockForUpdate()->find($item['id']);

                if (! $product || ! $product->active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => 'El producto "' . $item['name'] . '" ya no tiene stock suficiente.',
                    ]);
                }

                $product->decrement('stock', $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            return $order;
        });
    }

    public function updateStatus(Order $order, string $status): void
    {
        $order->update(['status' => $status]);
    }
}
