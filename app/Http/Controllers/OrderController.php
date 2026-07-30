<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function checkout()
    {
        return view('orders.checkout', [
            'cart' => session('cart', []),
            'total' => $this->cartTotal(),
        ]);
    }

    public function store(Request $request)
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('catalog.index')->with('error', 'El carrito esta vacio.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'delivery_address' => ['required', 'string', 'max:500'],
        ]);

        $order = DB::transaction(function () use ($cart, $validated) {
            $order = Order::create([
                ...$validated,
                'user_id' => auth()->id(),
                'code' => 'PED-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'total' => $this->cartTotal(),
            ]);

            foreach ($cart as $item) {
                $product = Product::lockForUpdate()->find($item['id']);

                if (! $product || ! $product->active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => 'El producto "' . $item['name'] . '" ya no tiene stock suficiente.',
                    ]);
                }

                $product->decrement('stock', $item['quantity']);

                $order->items()->create([
                    'product_id' => $product?->id,
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            return $order;
        });

        session()->forget('cart');

        return redirect()->route('orders.show', $order)->with('success', 'Pedido realizado correctamente.');
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $search = (string) $request->string('buscar');

                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items');

        return view('orders.show', compact('order'));
    }

    public function mine()
    {
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('orders.mine', compact('orders'));
    }

    public function myOrder(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load('items');

        return view('orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', Order::STATUSES)],
        ]);

        $order->update($validated);

        return back()->with('success', 'Estado del pedido actualizado.');
    }

    private function cartTotal(): float
    {
        return collect(session('cart', []))->sum(fn ($item) => $item['price'] * $item['quantity']);
    }
}
