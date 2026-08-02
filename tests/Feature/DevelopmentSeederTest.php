<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSourceMapping;
use App\Models\ProductStockSubscription;
use App\Models\ShippingCity;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('seeds a varied and internally consistent development dataset', function () {
    Storage::fake('public');

    $this->seed();

    $products = Product::query()
        ->where('canonical_key', 'like', 'dev-seed-%')
        ->with(['images', 'category'])
        ->get();
    $users = User::query()
        ->where('email', 'like', 'dev.user%@example.test')
        ->get();
    $orders = Order::query()
        ->where('code', 'like', 'DEV-%')
        ->with(['items', 'statusHistory', 'userAddress.shippingCity'])
        ->get();

    expect($products)->toHaveCount(100)
        ->and($products->pluck('category_id')->unique())->toHaveCount(10)
        ->and($products->where('stock', 0))->toHaveCount(14)
        ->and($products->whereNotNull('promotional_price'))->toHaveCount(50)
        ->and($products->filter->hasActivePromotion())->toHaveCount(30)
        ->and($products->filter(fn (Product $product) => $product->images->isEmpty()))->toHaveCount(12)
        ->and(ProductImage::query()->where('source', 'seed')->count())->toBeGreaterThan(100)
        ->and(ProductSourceMapping::count())->toBe(0)
        ->and($users)->toHaveCount(200)
        ->and(UserAddress::count())->toBeGreaterThanOrEqual(200)
        ->and(ShippingCity::query()->where('active', false)->count())->toBe(2)
        ->and($orders)->toHaveCount(300)
        ->and($orders->pluck('status')->unique()->sort()->values()->all())
        ->toBe(collect(Order::STATUSES)->sort()->values()->all())
        ->and(ProductStockSubscription::query()->where('status', 'pending')->count())->toBe(28)
        ->and(ProductStockSubscription::query()->where('status', 'notified')->count())->toBe(12)
        ->and(User::query()->where('email', 'admin@example.com')->exists())->toBeTrue();

    $image = $products->first(fn (Product $product) => $product->images->isNotEmpty())->images->first();
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $image->image_url));

    foreach ($orders as $order) {
        $itemsSubtotal = round($order->items->sum(fn ($item) => (float) $item->subtotal), 2);

        expect(round((float) $order->subtotal, 2))->toBe($itemsSubtotal)
            ->and(round((float) $order->total, 2))
            ->toBe(round($itemsSubtotal + (float) $order->shipping_cost, 2))
            ->and($order->delivery_address_line_1)->not->toBeEmpty()
            ->and($order->shipping_city_name)->not->toBeEmpty()
            ->and($order->statusHistory)->not->toBeEmpty()
            ->and($order->statusHistory->first()->status)->toBe($order->status);

        foreach ($order->items as $item) {
            expect((float) $item->regular_unit_price)->toBeGreaterThanOrEqual((float) $item->unit_price)
                ->and(round((float) $item->subtotal, 2))
                ->toBe(round((float) $item->unit_price * $item->quantity, 2));
        }
    }
});

it('can rerun development seeders without duplicating their records', function () {
    Storage::fake('public');

    $this->seed();
    $this->seed();

    expect(Product::query()->where('canonical_key', 'like', 'dev-seed-%')->count())->toBe(100)
        ->and(User::query()->where('email', 'like', 'dev.user%@example.test')->count())->toBe(200)
        ->and(Order::query()->where('code', 'like', 'DEV-%')->count())->toBe(300)
        ->and(ProductStockSubscription::query()->where('email', 'like', 'stock.dev.%@example.test')->count())->toBe(40);
});
