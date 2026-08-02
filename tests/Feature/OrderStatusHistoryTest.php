<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ShippingCity;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function orderTrackingCategory(string $name): Category
{
    return Category::query()->firstOrCreate([
        'slug' => Category::makeSlug($name),
    ], [
        'name' => trim($name),
    ]);
}

function trackedOrder(OrderService $service, ?int $userId = null): Order
{
    $shippingCity = ShippingCity::create([
        'name' => 'Asuncion',
        'shipping_cost' => 15,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Producto tracking',
        'category_id' => orderTrackingCategory('General')->id,
        'description' => 'Desc',
        'price' => 45,
        'stock' => 5,
        'active' => true,
    ]);

    return $service->createFromCart([
        [
            'id' => $product->id,
            'name' => $product->name,
            'price' => 45.0,
            'regular_price' => 45.0,
            'quantity' => 1,
        ],
    ], [
        'customer_name' => 'Cliente Tracking',
        'customer_email' => 'cliente-tracking@test.com',
        'customer_phone' => '555-000',
        'delivery_address' => 'Av. Historial 123',
    ], $userId, $shippingCity->id);
}

it('creates an initial status history entry when an order is created', function () {
    $order = trackedOrder(app(OrderService::class));

    $history = OrderStatusHistory::where('order_id', $order->id)->get();

    expect($history)->toHaveCount(1)
        ->and($history->first()->status)->toBe('pendiente')
        ->and($history->first()->assigned_at)->not->toBeNull()
        ->and($order->fresh()->status)->toBe('pendiente');
});

it('records a new history row only when the order status actually changes', function () {
    $admin = User::create([
        'name' => 'Admin Tracking',
        'email' => 'admin-tracking@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $order = trackedOrder(app(OrderService::class));

    $this->actingAs($admin)
        ->from(route('orders.show', $order))
        ->patch(route('orders.status', $order), ['status' => 'confirmado'])
        ->assertRedirect();

    $order->refresh();

    expect($order->status)->toBe('confirmado')
        ->and($order->statusHistory()->count())->toBe(2)
        ->and($order->statusHistory()->first()->status)->toBe('confirmado');

    $this->actingAs($admin)
        ->from(route('orders.show', $order))
        ->patch(route('orders.status', $order), ['status' => 'confirmado'])
        ->assertRedirect();

    expect($order->fresh()->statusHistory()->count())->toBe(2);
});

it('shows complete status history in admin and customer order detail views', function () {
    $service = app(OrderService::class);

    $admin = User::create([
        'name' => 'Admin Historial',
        'email' => 'admin-historial@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $customer = User::create([
        'name' => 'Cliente Historial',
        'email' => 'cliente-historial@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $otherCustomer = User::create([
        'name' => 'Otro Cliente',
        'email' => 'otro-cliente@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $order = trackedOrder($service, $customer->id);
    $service->updateStatus($order, 'confirmado');
    $service->updateStatus($order->fresh(), 'enviado');
    $order = $order->fresh();

    $adminResponse = $this->actingAs($admin)->get(route('orders.show', $order));
    $adminResponse->assertOk();
    $adminResponse->assertSee('Historial de estados');
    $adminResponse->assertSee('Pendiente');
    $adminResponse->assertSee('Confirmado');
    $adminResponse->assertSee('Enviado');

    $customerResponse = $this->actingAs($customer)->get(route('orders.mine.show', $order));
    $customerResponse->assertOk();
    $customerResponse->assertSee('Historial de estados');
    $customerResponse->assertSee('Enviado');
    $customerResponse->assertSee('Estado actual');

    $this->actingAs($otherCustomer)
        ->get(route('orders.mine.show', $order))
        ->assertForbidden();

    expect($order->status)->toBe('enviado')
        ->and($order->statusHistory()->count())->toBe(3);
});
