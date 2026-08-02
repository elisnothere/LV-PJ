<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingCityController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\UserController;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
    Route::get('/registro', [AuthController::class, 'register'])->name('register');
    Route::post('/registro', [AuthController::class, 'storeRegister'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/hola', function () {
    return 'Hola mundo';
});

Route::get('/categoria', [CatalogController::class, 'categories'])->name('categories.index');
Route::view('/contacto', 'contacto');

Route::get('test', function () {
    try {
        DB::connection()->getPdo();

        return 'Conexion exitosa a la base de datos: ' . DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        return 'Error al conectar a la base de datos: ' . $e->getMessage();
    }
});

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalogo/{product}', [CatalogController::class, 'show'])->name('catalog.show');
Route::post('/catalogo/{product}/stock-subscriptions', [CatalogController::class, 'subscribe'])->name('product-stock-subscriptions.store');

Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/carrito', [CartController::class, 'clear'])->name('cart.clear');

Route::middleware('auth')->group(function () {
    Route::get('/mis-direcciones', [UserAddressController::class, 'index'])->name('addresses.index');
    Route::get('/mis-direcciones/create', [UserAddressController::class, 'create'])->name('addresses.create');
    Route::post('/mis-direcciones', [UserAddressController::class, 'store'])->name('addresses.store');
    Route::get('/mis-direcciones/{address}/edit', [UserAddressController::class, 'edit'])->name('addresses.edit');
    Route::put('/mis-direcciones/{address}', [UserAddressController::class, 'update'])->name('addresses.update');
    Route::delete('/mis-direcciones/{address}', [UserAddressController::class, 'destroy'])->name('addresses.destroy');

    Route::get('/pedidos/checkout', [OrderController::class, 'checkout'])->name('orders.checkout');
    Route::post('/pedidos', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/mis-pedidos', [OrderController::class, 'mine'])->name('orders.mine');
    Route::get('/mis-pedidos/{order}', [OrderController::class, 'myOrder'])->name('orders.mine.show');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::redirect('/create', '/usuarios/create');
    Route::patch('/usuarios/{usuario}/activo', [UserController::class, 'toggleActive'])->name('usuarios.active');
    Route::resource('usuarios', UserController::class)->parameters(['usuarios' => 'usuario']);

    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('/categoria', [CatalogController::class, 'storeCategory'])->name('categories.store');
    Route::patch('/categoria/{category}', [CatalogController::class, 'updateCategory'])->name('categories.update');

    Route::get('/shipping-cities', [ShippingCityController::class, 'index'])->name('shipping-cities.index');
    Route::get('/shipping-cities/create', [ShippingCityController::class, 'create'])->name('shipping-cities.create');
    Route::post('/shipping-cities', [ShippingCityController::class, 'store'])->name('shipping-cities.store');
    Route::get('/shipping-cities/{shippingCity}/edit', [ShippingCityController::class, 'edit'])->name('shipping-cities.edit');
    Route::put('/shipping-cities/{shippingCity}', [ShippingCityController::class, 'update'])->name('shipping-cities.update');
    Route::patch('/shipping-cities/{shippingCity}/activo', [ShippingCityController::class, 'toggleActive'])->name('shipping-cities.active');

    Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/pedidos/{order}/estado', [OrderController::class, 'updateStatus'])->name('orders.status');
});
