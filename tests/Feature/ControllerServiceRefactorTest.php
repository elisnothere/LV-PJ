<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingCity;
use App\Models\User;
use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\OrderService;
use App\Services\UserManagementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function categoryForTest(string $name): Category
{
    return Category::query()->firstOrCreate([
        'slug' => Category::makeSlug($name),
    ], [
        'name' => trim($name),
    ]);
}

function attachPrimaryImage(Product $product, string $imageUrl = '/storage/productos/test-product.jpg'): void
{
    $product->images()->create([
        'image_url' => $imageUrl,
        'source' => 'upload',
        'is_primary' => true,
        'sort_order' => 1,
    ]);

    $product->forceFill(['image_url' => $imageUrl])->save();
}

it('authenticates an active user through the login endpoint', function () {
    $user = User::create([
        'name' => 'Cliente',
        'email' => 'cliente@example.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $response = $this->post(route('login.authenticate'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('catalog.index'));
    $this->assertAuthenticatedAs($user);
});

it('prevents inactive users from logging in', function () {
    User::create([
        'name' => 'Inactivo',
        'email' => 'inactive@example.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => false,
    ]);

    $response = $this->from(route('login'))->post(route('login.authenticate'), [
        'email' => 'inactive@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('registers a new customer through the register endpoint', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Nuevo Cliente',
        'email' => 'nuevo@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('catalog.index'));
    $this->assertDatabaseHas('users', [
        'email' => 'nuevo@example.com',
        'role' => 'cliente',
        'active' => 1,
    ]);
});

it('cart service adds updates removes and clears items', function () {
    Session::start();
    $service = app(CartService::class);

    $product = Product::create([
        'name' => 'Producto',
        'category_id' => categoryForTest('General')->id,
        'description' => 'Desc',
        'price' => 10,
        'stock' => 5,
        'active' => true,
    ]);

    $service->add($product, 2);
    expect($service->contents()[$product->id]['quantity'])->toBe(2);
    expect($service->total())->toBe(20.0);

    $service->update($product, 4);
    expect($service->contents()[$product->id]['quantity'])->toBe(4);

    $service->remove($product);
    expect($service->contents())->toBe([]);

    $service->add($product, 1);
    $service->clear();
    expect($service->contents())->toBe([]);
});

it('creates an order and decrements stock through the order service', function () {
    $service = app(OrderService::class);
    $shippingCity = ShippingCity::create([
        'name' => 'Asuncion',
        'shipping_cost' => 15,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Producto',
        'category_id' => categoryForTest('General')->id,
        'description' => 'Desc',
        'price' => 25,
        'stock' => 5,
        'active' => true,
    ]);
    attachPrimaryImage($product, '/storage/productos/order-service.jpg');

    $order = $service->createFromCart([
        [
            'id' => $product->id,
            'name' => $product->name,
            'price' => 25.0,
            'regular_price' => 25.0,
            'quantity' => 2,
        ],
    ], [
        'customer_name' => 'Cliente',
        'customer_email' => 'cliente@example.com',
        'customer_phone' => '123',
        'delivery_address' => 'Calle 123',
    ], null, $shippingCity->id);

    $product->refresh();

    expect($order->items)->toHaveCount(1)
        ->and($product->stock)->toBe(3)
        ->and((float) $order->subtotal)->toBe(50.0)
        ->and((float) $order->shipping_cost)->toBe(15.0)
        ->and((float) $order->total)->toBe(65.0)
        ->and((float) $order->items->first()->regular_unit_price)->toBe(25.0)
        ->and($order->items->first()->product_image_url)->toBe('/storage/productos/order-service.jpg')
        ->and($order->shipping_city_name)->toBe('Asuncion');
});

it('fails order creation when stock is insufficient', function () {
    $service = app(OrderService::class);
    $shippingCity = ShippingCity::create([
        'name' => 'Central',
        'shipping_cost' => 10,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Producto',
        'category_id' => categoryForTest('General')->id,
        'description' => 'Desc',
        'price' => 25,
        'stock' => 1,
        'active' => true,
    ]);

    $this->expectException(ValidationException::class);

    $service->createFromCart([
        [
            'id' => $product->id,
            'name' => $product->name,
            'price' => 25.0,
            'regular_price' => 25.0,
            'quantity' => 2,
        ],
    ], [
        'customer_name' => 'Cliente',
        'customer_email' => 'cliente@example.com',
        'customer_phone' => '123',
        'delivery_address' => 'Calle 123',
    ], null, $shippingCity->id);
});

it('creates a product with a newly entered category through the admin flow', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin-products@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'Parlante Bluetooth',
        'new_category_name' => 'Audio',
        'description' => 'Portatil',
        'price' => 150,
        'stock' => 6,
        'active' => 1,
    ]);

    $response->assertRedirect(route('products.index'));

    $product = Product::with('category')->first();

    expect(Category::count())->toBe(1)
        ->and($product->category?->name)->toBe('Audio');
});

it('validates promotional price and date range in the admin product flow', function () {
    $admin = User::create([
        'name' => 'Admin Promo',
        'email' => 'admin-promo@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $response = $this->from(route('products.create'))->actingAs($admin)->post(route('products.store'), [
        'name' => 'Monitor 4K',
        'new_category_name' => 'Displays',
        'description' => 'Desc',
        'price' => 100,
        'promotional_price' => 120,
        'promotional_starts_at' => '2026-08-02T10:00',
        'promotional_ends_at' => '2026-08-01T10:00',
        'stock' => 4,
        'active' => 1,
    ]);

    $response->assertRedirect(route('products.create'));
    $response->assertSessionHasErrors(['promotional_price', 'promotional_ends_at']);
});

it('uses promotional price in catalog cart checkout and order snapshots', function () {
    Carbon::setTestNow('2026-08-02 12:00:00');
    Session::start();

    $user = User::create([
        'name' => 'Cliente Promo',
        'email' => 'cliente-promo@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'San Lorenzo',
        'shipping_cost' => 12.5,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Auriculares',
        'category_id' => categoryForTest('Audio')->id,
        'description' => 'Desc',
        'price' => 40,
        'promotional_price' => 30,
        'promotional_starts_at' => '2026-08-01 00:00:00',
        'promotional_ends_at' => '2026-08-03 23:59:59',
        'stock' => 5,
        'active' => true,
    ]);
    attachPrimaryImage($product, '/storage/productos/promo-auriculares.jpg');

    $catalogResponse = $this->get(route('catalog.index'));
    $catalogResponse->assertOk();
    $catalogResponse->assertSee('$30.00', false);
    $catalogResponse->assertSee('text-decoration-line-through', false);

    $detailResponse = $this->get(route('catalog.show', $product));
    $detailResponse->assertOk();
    $detailResponse->assertSee('$30.00', false);
    $detailResponse->assertSee('$40.00', false);

    $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 2]);

    $cartResponse = $this->actingAs($user)->get(route('cart.index'));
    $cartResponse->assertOk();
    $cartResponse->assertSee('$30.00', false);
    $cartResponse->assertSee('$60.00', false);
    $cartResponse->assertSee('/storage/productos/promo-auriculares.jpg', false);

    $checkoutResponse = $this->actingAs($user)->get(route('orders.checkout', ['shipping_city_id' => $shippingCity->id]));
    $checkoutResponse->assertOk();
    $checkoutResponse->assertSee('$72.50', false);
    $checkoutResponse->assertSee('/storage/productos/promo-auriculares.jpg', false);

    $storeResponse = $this->actingAs($user)->post(route('orders.store'), [
        'customer_name' => 'Cliente Promo',
        'customer_email' => 'cliente-promo@test.com',
        'customer_phone' => '555-123',
        'delivery_address' => 'Av. Siempre Viva',
        'shipping_city_id' => $shippingCity->id,
    ]);

    $storeResponse->assertRedirect();
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'shipping_city_id' => $shippingCity->id,
        'subtotal' => 60,
        'shipping_cost' => 12.5,
        'total' => 72.5,
    ]);

    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'product_image_url' => '/storage/productos/promo-auriculares.jpg',
        'unit_price' => 30,
        'regular_unit_price' => 40,
        'subtotal' => 60,
    ]);

    Carbon::setTestNow();
});

it('shows a fallback placeholder when cart or checkout items do not have images', function () {
    Session::start();

    $user = User::create([
        'name' => 'Cliente Fallback',
        'email' => 'cliente-fallback@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'Lambaré',
        'shipping_cost' => 8,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Producto sin imagen',
        'category_id' => categoryForTest('General')->id,
        'description' => 'Desc',
        'price' => 20,
        'stock' => 2,
        'active' => true,
    ]);

    $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 1]);

    $cartResponse = $this->actingAs($user)->get(route('cart.index'));
    $cartResponse->assertOk();
    $cartResponse->assertSee('data:image/svg+xml', false);

    $checkoutResponse = $this->actingAs($user)->get(route('orders.checkout', ['shipping_city_id' => $shippingCity->id]));
    $checkoutResponse->assertOk();
    $checkoutResponse->assertSee('data:image/svg+xml', false);
});

it('does not apply a future or expired promotion', function () {
    Carbon::setTestNow('2026-08-02 12:00:00');

    $futureProduct = Product::create([
        'name' => 'Tablet',
        'category_id' => categoryForTest('Electronics')->id,
        'description' => 'Desc',
        'price' => 200,
        'promotional_price' => 150,
        'promotional_starts_at' => '2026-08-03 00:00:00',
        'stock' => 2,
        'active' => true,
    ]);

    $expiredProduct = Product::create([
        'name' => 'Camara',
        'category_id' => categoryForTest('Electronics')->id,
        'description' => 'Desc',
        'price' => 300,
        'promotional_price' => 250,
        'promotional_ends_at' => '2026-08-01 23:59:59',
        'stock' => 2,
        'active' => true,
    ]);

    expect($futureProduct->hasActivePromotion())->toBeFalse()
        ->and($futureProduct->effectivePrice())->toBe(200.0)
        ->and($expiredProduct->hasActivePromotion())->toBeFalse()
        ->and($expiredProduct->effectivePrice())->toBe(300.0);

    Carbon::setTestNow();
});

it('reuses equivalent category names without creating duplicates', function () {
    $service = app(CategoryService::class);

    $first = $service->resolveByName('Electronics & Gadgets');
    $second = $service->resolveByName(' electronics gadgets ');

    expect($first->id)->toBe($second->id)
        ->and(Category::count())->toBe(1);
});

it('allows admins to create categories from the category page', function () {
    $admin = User::create([
        'name' => 'Admin Categorias',
        'email' => 'admin-categories@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $response = $this->actingAs($admin)->post(route('categories.store'), [
        'name' => 'Iluminacion',
    ]);

    $response->assertRedirect('/categoria');
    $this->assertDatabaseHas('categories', [
        'name' => 'Iluminacion',
        'slug' => 'iluminacion',
    ]);
});

it('allows admins to rename categories from the category page', function () {
    $admin = User::create([
        'name' => 'Admin Categorias',
        'email' => 'admin-categories-update@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $category = categoryForTest('Hogar');

    $response = $this->actingAs($admin)->patch(route('categories.update', $category), [
        'name' => 'Casa y hogar',
    ]);

    $response->assertRedirect('/categoria');
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Casa y hogar',
        'slug' => 'casa-y-hogar',
    ]);
});

it('forbids non admins from modifying categories', function () {
    $user = User::create([
        'name' => 'Cliente',
        'email' => 'cliente-categories@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $category = categoryForTest('Libros');

    $response = $this->actingAs($user)->patch(route('categories.update', $category), [
        'name' => 'Papeleria',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Libros',
        'slug' => 'libros',
    ]);
});

it('allows admins to manage shipping cities from the dashboard', function () {
    $admin = User::create([
        'name' => 'Admin Envios',
        'email' => 'admin-envios@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $createResponse = $this->actingAs($admin)->post(route('shipping-cities.store'), [
        'name' => 'Luque',
        'shipping_cost' => 18.5,
        'active' => 1,
    ]);

    $createResponse->assertRedirect(route('shipping-cities.index'));
    $this->assertDatabaseHas('shipping_cities', [
        'name' => 'Luque',
        'shipping_cost' => 18.5,
        'active' => 1,
    ]);

    $shippingCity = ShippingCity::where('name', 'Luque')->firstOrFail();

    $updateResponse = $this->actingAs($admin)->put(route('shipping-cities.update', $shippingCity), [
        'name' => 'Luque Centro',
        'shipping_cost' => 22,
        'active' => 1,
    ]);

    $updateResponse->assertRedirect(route('shipping-cities.index'));
    $this->assertDatabaseHas('shipping_cities', [
        'id' => $shippingCity->id,
        'name' => 'Luque Centro',
        'shipping_cost' => 22,
        'active' => 1,
    ]);

    $toggleResponse = $this->actingAs($admin)->patch(route('shipping-cities.active', $shippingCity));

    $toggleResponse->assertRedirect();
    $this->assertDatabaseHas('shipping_cities', [
        'id' => $shippingCity->id,
        'active' => 0,
    ]);
});

it('filters the catalog by category through the relationship', function () {
    $general = categoryForTest('General');
    $fitness = categoryForTest('Fitness');

    Product::create([
        'name' => 'Producto General',
        'category_id' => $general->id,
        'description' => 'Desc',
        'price' => 20,
        'stock' => 1,
        'active' => true,
    ]);

    Product::create([
        'name' => 'Producto Fitness',
        'category_id' => $fitness->id,
        'description' => 'Desc',
        'price' => 30,
        'stock' => 1,
        'active' => true,
    ]);

    $response = $this->get(route('catalog.index', ['categoria' => $general->slug]));

    $response->assertOk();
    $response->assertSee('Producto General');
    $response->assertDontSee('Producto Fitness');
});

it('loads category summary through the route-backed controller action', function () {
    Product::create([
        'name' => 'Producto',
        'category_id' => categoryForTest('General')->id,
        'description' => 'Desc',
        'price' => 20,
        'stock' => 1,
        'active' => true,
    ]);

    $response = $this->get('/categoria');

    $response->assertOk();
    $response->assertSee('General');
});

it('calculates shipping in checkout and persists an order snapshot', function () {
    Session::start();

    $user = User::create([
        'name' => 'Cliente Pedido',
        'email' => 'cliente-pedido@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'San Lorenzo',
        'shipping_cost' => 12.5,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Auriculares',
        'category_id' => categoryForTest('Audio')->id,
        'description' => 'Desc',
        'price' => 40,
        'stock' => 5,
        'active' => true,
    ]);
    attachPrimaryImage($product, '/storage/productos/checkout-order.jpg');

    $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 2]);

    $checkoutResponse = $this->actingAs($user)->get(route('orders.checkout', ['shipping_city_id' => $shippingCity->id]));

    $checkoutResponse->assertOk();
    $checkoutResponse->assertSee('San Lorenzo');
    $checkoutResponse->assertSee('$12.50', false);
    $checkoutResponse->assertSee('$92.50', false);
    $checkoutResponse->assertSee('/storage/productos/checkout-order.jpg', false);

    $storeResponse = $this->actingAs($user)->post(route('orders.store'), [
        'customer_name' => 'Cliente Pedido',
        'customer_email' => 'cliente-pedido@test.com',
        'customer_phone' => '555-123',
        'delivery_address' => 'Av. Siempre Viva',
        'shipping_city_id' => $shippingCity->id,
    ]);

    $storeResponse->assertRedirect();
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'shipping_city_id' => $shippingCity->id,
        'shipping_city_name' => 'San Lorenzo',
        'subtotal' => 80,
        'shipping_cost' => 12.5,
        'total' => 92.5,
    ]);
    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'product_image_url' => '/storage/productos/checkout-order.jpg',
    ]);

    $shippingCity->update(['shipping_cost' => 99]);

    $order = \App\Models\Order::latest()->first();
    expect((float) $order->shipping_cost)->toBe(12.5)
        ->and((float) $order->total)->toBe(92.5);
});

it('preserves the historical order image even if the product image changes later', function () {
    $user = User::create([
        'name' => 'Cliente Imagen',
        'email' => 'cliente-imagen@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'Fernando',
        'shipping_cost' => 5,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Mouse gamer',
        'category_id' => categoryForTest('Gaming')->id,
        'description' => 'Desc',
        'price' => 55,
        'stock' => 3,
        'active' => true,
    ]);
    attachPrimaryImage($product, '/storage/productos/order-original.jpg');

    $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 1]);
    $this->actingAs($user)->post(route('orders.store'), [
        'customer_name' => 'Cliente Imagen',
        'customer_email' => 'cliente-imagen@test.com',
        'customer_phone' => '555-123',
        'delivery_address' => 'Av. Imagen',
        'shipping_city_id' => $shippingCity->id,
    ])->assertRedirect();

    $product->images()->update(['is_primary' => false]);
    $product->images()->create([
        'image_url' => '/storage/productos/order-updated.jpg',
        'source' => 'upload',
        'is_primary' => true,
        'sort_order' => 2,
    ]);
    $product->forceFill(['image_url' => '/storage/productos/order-updated.jpg'])->save();

    $order = \App\Models\Order::with('items')->latest()->first();

    $response = $this->actingAs($user)->get(route('orders.mine.show', $order));
    $response->assertOk();
    $response->assertSee('/storage/productos/order-original.jpg', false);
    $response->assertDontSee('/storage/productos/order-updated.jpg', false);
});

it('rejects inactive shipping cities during checkout', function () {
    $user = User::create([
        'name' => 'Cliente Pedido',
        'email' => 'cliente-envio-inactivo@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'Inactiva',
        'shipping_cost' => 5,
        'active' => false,
    ]);

    $product = Product::create([
        'name' => 'Teclado',
        'category_id' => categoryForTest('Perifericos')->id,
        'description' => 'Desc',
        'price' => 30,
        'stock' => 5,
        'active' => true,
    ]);

    $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 1]);

    $response = $this->from(route('orders.checkout'))->actingAs($user)->post(route('orders.store'), [
        'customer_name' => 'Cliente Pedido',
        'customer_email' => 'cliente-envio-inactivo@test.com',
        'customer_phone' => '555-123',
        'delivery_address' => 'Av. Siempre Viva',
        'shipping_city_id' => $shippingCity->id,
    ]);

    $response->assertRedirect(route('orders.checkout'));
    $response->assertSessionHasErrors('shipping_city_id');
});

it('prevents deleting your own user through the management service', function () {
    $service = app(UserManagementService::class);
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $this->expectException(DomainException::class);
    $service->delete($user, $user->id);
});

