<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\OrderService;
use App\Services\UserManagementService;
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

    $product = Product::create([
        'name' => 'Producto',
        'category_id' => categoryForTest('General')->id,
        'description' => 'Desc',
        'price' => 25,
        'stock' => 5,
        'active' => true,
    ]);

    $order = $service->createFromCart([
        [
            'id' => $product->id,
            'name' => $product->name,
            'price' => 25.0,
            'quantity' => 2,
        ],
    ], [
        'customer_name' => 'Cliente',
        'customer_email' => 'cliente@example.com',
        'customer_phone' => '123',
        'delivery_address' => 'Calle 123',
    ], null);

    $product->refresh();

    expect($order->items)->toHaveCount(1)
        ->and($product->stock)->toBe(3)
        ->and((float) $order->total)->toBe(50.0);
});

it('fails order creation when stock is insufficient', function () {
    $service = app(OrderService::class);

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
            'quantity' => 2,
        ],
    ], [
        'customer_name' => 'Cliente',
        'customer_email' => 'cliente@example.com',
        'customer_phone' => '123',
        'delivery_address' => 'Calle 123',
    ], null);
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
