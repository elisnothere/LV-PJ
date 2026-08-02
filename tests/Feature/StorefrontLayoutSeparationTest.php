<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingCity;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

function storefrontCategory(string $name): Category
{
    return Category::query()->firstOrCreate([
        'slug' => Category::makeSlug($name),
    ], [
        'name' => trim($name),
    ]);
}

it('renders customer-facing pages with the storefront layout', function () {
    Product::create([
        'name' => 'Producto publico',
        'category_id' => storefrontCategory('General')->id,
        'description' => 'Desc',
        'price' => 25,
        'stock' => 3,
        'active' => true,
    ]);

    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertSee('Experiencia de compra separada del panel administrativo.')
        ->assertDontSee('Panel administrativo');

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSee('Experiencia de compra separada del panel administrativo.')
        ->assertDontSee('Panel administrativo');

    $this->get('/contacto')
        ->assertOk()
        ->assertSee('Experiencia de compra separada del panel administrativo.')
        ->assertDontSee('Panel administrativo');
});

it('renders cart checkout and customer order pages with the storefront layout', function () {
    Session::start();

    $user = User::create([
        'name' => 'Cliente Layout',
        'email' => 'cliente-layout@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'Asuncion',
        'shipping_cost' => 15,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Mouse',
        'category_id' => storefrontCategory('Perifericos')->id,
        'description' => 'Desc',
        'price' => 35,
        'stock' => 4,
        'active' => true,
    ]);

    $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 1]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Experiencia de compra separada del panel administrativo.')
        ->assertDontSee('Panel administrativo');

    $this->actingAs($user)->get(route('orders.checkout', ['shipping_city_id' => $shippingCity->id]))
        ->assertOk()
        ->assertSee('Experiencia de compra separada del panel administrativo.')
        ->assertDontSee('Panel administrativo');

    $storeResponse = $this->actingAs($user)->post(route('orders.store'), [
        'customer_name' => 'Cliente Layout',
        'customer_email' => 'cliente-layout@test.com',
        'customer_phone' => '555-111',
        'delivery_address' => 'Calle 123',
        'shipping_city_id' => $shippingCity->id,
    ]);

    $order = \App\Models\Order::latest()->firstOrFail();
    $storeResponse->assertRedirect(route('orders.show', $order));

    $this->actingAs($user)->get(route('orders.mine'))
        ->assertOk()
        ->assertSee('Experiencia de compra separada del panel administrativo.')
        ->assertDontSee('Panel administrativo');

    $this->actingAs($user)->get(route('orders.mine.show', $order))
        ->assertOk()
        ->assertSee('Experiencia de compra separada del panel administrativo.')
        ->assertDontSee('Panel administrativo');
});

it('keeps dashboard and order management views on the admin layout only', function () {
    $admin = User::create([
        'name' => 'Admin Layout',
        'email' => 'admin-layout@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'Luque',
        'shipping_cost' => 10,
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Silla',
        'category_id' => storefrontCategory('Oficina')->id,
        'description' => 'Desc',
        'price' => 120,
        'stock' => 5,
        'active' => true,
    ]);

    $order = app(OrderService::class)->createFromCart([
        [
            'id' => $product->id,
            'name' => $product->name,
            'price' => 120.0,
            'regular_price' => 120.0,
            'quantity' => 1,
        ],
    ], [
        'customer_name' => 'Cliente Pedido',
        'customer_email' => 'cliente-pedido@test.com',
        'customer_phone' => '555-222',
        'delivery_address' => 'Av. Admin',
    ], null, $shippingCity->id);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Panel administrativo')
        ->assertDontSee('Experiencia de compra separada del panel administrativo.')
        ->assertDontSee('>Catalogo<', false)
        ->assertDontSee('>Carrito<', false)
        ->assertDontSee('>Contacto<', false);

    $this->actingAs($admin)->get(route('orders.index'))
        ->assertOk()
        ->assertSee('Panel administrativo')
        ->assertDontSee('Experiencia de compra separada del panel administrativo.');

    $this->actingAs($admin)->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Panel administrativo')
        ->assertDontSee('Experiencia de compra separada del panel administrativo.');
});
