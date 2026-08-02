<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DevelopmentOrderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->where('email', 'like', 'dev.user%@example.test')
            ->where('role', 'cliente')
            ->with('addresses.shippingCity')
            ->orderBy('email')
            ->get();

        $products = Product::query()
            ->where('canonical_key', 'like', 'dev-seed-%')
            ->where('active', true)
            ->with('primaryImage')
            ->orderBy('id')
            ->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        $originalTestNow = Carbon::getTestNow();
        $seededAt = now()->copy();

        try {
            DB::transaction(function () use ($users, $products, $seededAt): void {
                Order::query()
                    ->where('code', 'like', 'DEV-%')
                    ->delete();

                for ($number = 1; $number <= 300; $number++) {
                    $user = $users[($number - 1) % $users->count()];
                    $addresses = $user->addresses;
                    $address = $addresses[($number - 1) % $addresses->count()];
                    $createdAt = $seededAt
                        ->copy()
                        ->subDays(4 + (($number * 11) % 362))
                        ->subHours($number % 20);
                    $lineItems = $this->lineItemsFor($products, $number, $createdAt);
                    $subtotal = round(array_sum(array_column($lineItems, 'subtotal')), 2);
                    $shippingCost = (float) $address->shippingCity->shipping_cost;

                    Carbon::setTestNow($createdAt);

                    $order = Order::query()->create([
                        'code' => sprintf('DEV-%06d', $number),
                        'user_id' => $user->id,
                        'shipping_city_id' => $address->shipping_city_id,
                        'user_address_id' => $address->id,
                        'customer_name' => $user->name,
                        'customer_email' => $user->email,
                        'customer_phone' => sprintf('+595 9%02d %03d %03d', $number % 90, ($number * 7) % 1000, ($number * 13) % 1000),
                        'delivery_address' => $address->formattedAddress(),
                        'delivery_address_line_1' => $address->primary_address,
                        'delivery_address_line_2' => $address->secondary_address,
                        'shipping_city_name' => $address->shippingCity->name,
                        'status' => Order::STATUSES[0],
                        'subtotal' => $subtotal,
                        'shipping_cost' => $shippingCost,
                        'total' => round($subtotal + $shippingCost, 2),
                    ]);

                    foreach ($lineItems as $lineItem) {
                        $order->items()->create($lineItem);
                    }

                    foreach ($this->statusPathFor($number) as $step => $status) {
                        Carbon::setTestNow(
                            $createdAt->copy()->addHours((($step + 1) * 9) + ($number % 5)),
                        );
                        $order->update(['status' => $status]);
                    }
                }
            });
        } finally {
            Carbon::setTestNow($originalTestNow);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lineItemsFor(Collection $products, int $orderNumber, Carbon $createdAt): array
    {
        $itemCount = 1 + ($orderNumber % 4);
        $lineItems = [];

        for ($position = 0; $position < $itemCount; $position++) {
            $product = $products[(($orderNumber * 13) + ($position * 17)) % $products->count()];
            $quantity = 1 + (($orderNumber + $position) % 3);
            $unitPrice = $product->effectivePrice($createdAt);
            $regularUnitPrice = (float) $product->price;

            $lineItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image_url' => $product->primary_image_url,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'regular_unit_price' => $regularUnitPrice,
                'subtotal' => round($unitPrice * $quantity, 2),
            ];
        }

        return $lineItems;
    }

    /**
     * @return array<int, string>
     */
    private function statusPathFor(int $orderNumber): array
    {
        return match ($orderNumber % 10) {
            0, 1 => [],
            2, 3 => ['confirmado'],
            4, 5 => ['confirmado', 'enviado'],
            6, 7, 8 => ['confirmado', 'enviado', 'entregado'],
            default => $orderNumber % 20 === 9
                ? ['cancelado']
                : ['confirmado', 'cancelado'],
        };
    }
}
