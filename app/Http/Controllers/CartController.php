<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        return view('cart.index', [
            'cart' => $this->cart(),
            'total' => $this->total(),
        ]);
    }

    public function add(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:' . max($product->stock, 1)],
        ]);

        if (! $product->active || $product->stock < 1) {
            return back()->with('error', 'El producto no esta disponible.');
        }

        $cart = $this->cart();
        $currentQuantity = $cart[$product->id]['quantity'] ?? 0;
        $newQuantity = min($currentQuantity + $validated['quantity'], $product->stock);

        $cart[$product->id] = [
            'id' => $product->id,
            'name' => $product->name,
            'image_url' => $product->image_url,
            'price' => (float) $product->price,
            'quantity' => $newQuantity,
            'stock' => $product->stock,
        ];

        session(['cart' => $cart]);

        return back()->with('success', 'Producto agregado al carrito.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:' . max($product->stock, 1)],
        ]);

        $cart = $this->cart();

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] = $validated['quantity'];
            session(['cart' => $cart]);
        }

        return back()->with('success', 'Carrito actualizado.');
    }

    public function remove(Product $product)
    {
        $cart = $this->cart();
        unset($cart[$product->id]);
        session(['cart' => $cart]);

        return back()->with('success', 'Producto removido del carrito.');
    }

    public function clear()
    {
        session()->forget('cart');

        return back()->with('success', 'Carrito vaciado.');
    }

    private function cart(): array
    {
        return session('cart', []);
    }

    private function total(): float
    {
        return collect($this->cart())->sum(fn ($item) => $item['price'] * $item['quantity']);
    }
}
