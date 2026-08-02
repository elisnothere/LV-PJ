<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\CartQuantityRequest;
use App\Models\Product;
use App\Services\CartService;
use DomainException;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function index()
    {
        $selectedAddress = auth()->check()
            ? $this->cartService->selectedAddress((int) auth()->id())
            : null;

        return view('cart.index', [
            'cart' => $this->cartService->contents(),
            'selectedAddress' => $selectedAddress,
            'selectedShippingCity' => auth()->check()
                ? $this->cartService->selectedShippingCity((int) auth()->id())
                : $this->cartService->selectedShippingCity(),
            'subtotal' => $this->cartService->subtotal(),
            'shippingCost' => auth()->check()
                ? $this->cartService->shippingCost((int) auth()->id())
                : $this->cartService->shippingCost(),
            'total' => auth()->check()
                ? $this->cartService->totalWithShipping((int) auth()->id())
                : $this->cartService->totalWithShipping(),
        ]);
    }

    public function add(CartQuantityRequest $request, Product $product)
    {
        try {
            $this->cartService->add($product, $request->quantity());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Producto agregado al carrito.');
    }

    public function update(CartQuantityRequest $request, Product $product)
    {
        $this->cartService->update($product, $request->quantity());

        return back()->with('success', 'Carrito actualizado.');
    }

    public function remove(Product $product)
    {
        $this->cartService->remove($product);

        return back()->with('success', 'Producto removido del carrito.');
    }

    public function clear()
    {
        $this->cartService->clear();

        return back()->with('success', 'Carrito vaciado.');
    }
}
