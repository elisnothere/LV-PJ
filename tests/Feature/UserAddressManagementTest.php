<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingCity;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

function addressCategory(string $name): Category
{
    return Category::query()->firstOrCreate([
        'slug' => Category::makeSlug($name),
    ], [
        'name' => trim($name),
    ]);
}

function createSavedAddress(User $user, ShippingCity $shippingCity, string $line1 = 'Av. Principal 123', ?string $line2 = 'Depto 4B'): UserAddress
{
    return UserAddress::create([
        'user_id' => $user->id,
        'shipping_city_id' => $shippingCity->id,
        'primary_address' => $line1,
        'secondary_address' => $line2,
    ]);
}

it('allows authenticated users to create edit list and delete their saved addresses', function () {
    $user = User::create([
        'name' => 'Cliente Direcciones',
        'email' => 'cliente-direcciones@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'Asuncion',
        'shipping_cost' => 15,
        'active' => true,
    ]);

    $this->actingAs($user)->get(route('addresses.index'))
        ->assertOk()
        ->assertSee('Direcciones guardadas');

    $createResponse = $this->actingAs($user)->post(route('addresses.store'), [
        'primary_address' => 'Av. Primera 123',
        'secondary_address' => 'Casa 2',
        'shipping_city_id' => $shippingCity->id,
    ]);

    $createResponse->assertRedirect(route('addresses.index'));
    $this->assertDatabaseHas('user_addresses', [
        'user_id' => $user->id,
        'primary_address' => 'Av. Primera 123',
        'secondary_address' => 'Casa 2',
        'shipping_city_id' => $shippingCity->id,
    ]);

    $address = UserAddress::firstOrFail();

    $updateResponse = $this->actingAs($user)->put(route('addresses.update', $address), [
        'primary_address' => 'Av. Primera 456',
        'secondary_address' => 'Casa 9',
        'shipping_city_id' => $shippingCity->id,
    ]);

    $updateResponse->assertRedirect(route('addresses.index'));
    $this->assertDatabaseHas('user_addresses', [
        'id' => $address->id,
        'primary_address' => 'Av. Primera 456',
        'secondary_address' => 'Casa 9',
    ]);

    $this->actingAs($user)->delete(route('addresses.destroy', $address))
        ->assertRedirect();

    $this->assertDatabaseMissing('user_addresses', [
        'id' => $address->id,
    ]);
});

it('prevents users from editing or deleting addresses they do not own', function () {
    $owner = User::create([
        'name' => 'Owner',
        'email' => 'owner-address@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $intruder = User::create([
        'name' => 'Intruder',
        'email' => 'intruder-address@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'Fernando',
        'shipping_cost' => 10,
        'active' => true,
    ]);

    $address = createSavedAddress($owner, $shippingCity);

    $this->actingAs($intruder)
        ->get(route('addresses.edit', $address))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put(route('addresses.update', $address), [
            'primary_address' => 'Cambio ilegal',
            'secondary_address' => null,
            'shipping_city_id' => $shippingCity->id,
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('addresses.destroy', $address))
        ->assertForbidden();

    $this->assertDatabaseHas('user_addresses', [
        'id' => $address->id,
        'user_id' => $owner->id,
    ]);
});

it('calculates shipping from the selected saved address and preserves its snapshot on the order', function () {
    Session::start();

    $user = User::create([
        'name' => 'Cliente Checkout',
        'email' => 'cliente-checkout-address@test.com',
        'password' => 'password123',
        'role' => 'cliente',
        'active' => true,
    ]);

    $shippingCity = ShippingCity::create([
        'name' => 'San Lorenzo',
        'shipping_cost' => 12.5,
        'active' => true,
    ]);

    $address = createSavedAddress($user, $shippingCity, 'Av. Siempre Viva 742', 'Torre Norte');

    $product = Product::create([
        'name' => 'Auriculares',
        'category_id' => addressCategory('Audio')->id,
        'description' => 'Desc',
        'price' => 40,
        'stock' => 5,
        'active' => true,
    ]);

    $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 2]);

    $checkoutResponse = $this->actingAs($user)->get(route('orders.checkout', ['user_address_id' => $address->id]));
    $checkoutResponse->assertOk();
    $checkoutResponse->assertSee('Av. Siempre Viva 742');
    $checkoutResponse->assertSee('San Lorenzo');
    $checkoutResponse->assertSee('$92.50', false);

    $storeResponse = $this->actingAs($user)->post(route('orders.store'), [
        'customer_name' => 'Cliente Checkout',
        'customer_email' => 'cliente-checkout-address@test.com',
        'customer_phone' => '555-123',
        'user_address_id' => $address->id,
    ]);

    $storeResponse->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'user_address_id' => $address->id,
        'shipping_city_id' => $shippingCity->id,
        'shipping_city_name' => 'San Lorenzo',
        'delivery_address_line_1' => 'Av. Siempre Viva 742',
        'delivery_address_line_2' => 'Torre Norte',
        'shipping_cost' => 12.5,
        'subtotal' => 80,
        'total' => 92.5,
    ]);

    $address->update([
        'primary_address' => 'Av. Cambiada 999',
        'secondary_address' => 'Otro bloque',
    ]);
    $shippingCity->update(['shipping_cost' => 99]);

    $order = \App\Models\Order::latest()->firstOrFail();

    expect($order->delivery_address_line_1)->toBe('Av. Siempre Viva 742')
        ->and($order->delivery_address_line_2)->toBe('Torre Norte')
        ->and((float) $order->shipping_cost)->toBe(12.5)
        ->and((float) $order->total)->toBe(92.5);
});
