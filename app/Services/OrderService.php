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
    public function __construct(private ShippingCityService $shippingCityService)
    {
    }

    public function createFromCart(array $cart, array $orderData, ?int $userId, int $shippingCityId): Order
    {
        if (empty($cart)) {
            throw new DomainException('El carrito esta vacio.');
        }

        return DB::transaction(function () use ($cart, $orderData, $userId, $shippingCityId) {
            $shippingCity = $this->shippingCityService->resolveActiveOrFail($shippingCityId);

            $order = Order::create([
                ...$orderData,
                'user_id' => $userId,
                'shipping_city_id' => $shippingCity->id,
                'shipping_city_name' => $shippingCity->name,
                'code' => 'PED-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'subtotal' => 0,
                'shipping_cost' => (float) $shippingCity->shipping_cost,
                'total' => (float) $shippingCity->shipping_cost,
            ]);

            $subtotal = 0.0;

            foreach ($cart as $item) {
                $product = Product::lockForUpdate()->with('primaryImage')->find($item['id']);

                if (! $product || ! $product->active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => 'El producto "' . $item['name'] . '" ya no tiene stock suficiente.',
                    ]);
                }

                $unitPrice = $product->effectivePrice();
                $regularUnitPrice = (float) $product->price;
                $itemSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $itemSubtotal;

                $product->decrement('stock', $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image_url' => $product->primary_image_url,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'regular_unit_price' => $regularUnitPrice,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            $shippingCost = (float) $shippingCity->shipping_cost;

            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal + $shippingCost,
            ]);

            return $order->fresh('items');
        });
    }

    public function updateStatus(Order $order, string $status): void
    {
        $order->update(['status' => $status]);
    }
}
