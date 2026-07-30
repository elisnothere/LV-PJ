<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Models\Product;

Route::get('/', function () {
    if (! auth()->check()) {
        return view('home');
    }

    return auth()->user()->role === 'admin'
        ? redirect()->route('dashboard')
        : redirect()->route('catalog.index');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
    Route::get('/registro', [AuthController::class, 'register'])->name('register');
    Route::post('/registro', [AuthController::class, 'storeRegister'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/hola', function(){ 
    return "Hola mundo";
});

Route::get('/categoria', function () {
    $categories = Product::query()
        ->where('active', true)
        ->selectRaw('category, count(*) as products_count')
        ->groupBy('category')
        ->orderBy('category')
        ->get();

    return view('partials.categoria', compact('categories'));
});
Route::view("/contacto","contacto");

Route::get("test", function(){
    try {
        DB::connection()->getPdo();
        return "Conexión exitosa a la base de datos: " . DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        return "Error al conectar a la base de datos: " . $e->getMessage();
    }
});  

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');

Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/carrito', [CartController::class, 'clear'])->name('cart.clear');

Route::middleware('auth')->group(function () {
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

    Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/pedidos/{order}/estado', [OrderController::class, 'updateStatus'])->name('orders.status');
});
