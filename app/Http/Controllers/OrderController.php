<?php

namespace App\Http\Controllers;

use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderQueryService;
use App\Services\OrderService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private OrderQueryService $orderQueryService,
    ) {
    }

    public function checkout()
    {
        return view('orders.checkout', [
            'cart' => $this->cartService->contents(),
            'total' => $this->cartService->total(),
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createFromCart(
                $this->cartService->contents(),
                $request->orderData(),
                auth()->id(),
            );
        } catch (DomainException $exception) {
            return redirect()->route('catalog.index')->with('error', $exception->getMessage());
        }

        $this->cartService->clear();

        return redirect()->route('orders.show', $order)->with('success', 'Pedido realizado correctamente.');
    }

    public function index(Request $request)
    {
        $search = $request->filled('buscar') ? (string) $request->string('buscar') : null;

        return view('orders.index', [
            'orders' => $this->orderQueryService->paginatedForAdmin($search),
        ]);
    }

    public function show(Order $order)
    {
        return view('orders.show', [
            'order' => $this->orderQueryService->loadForDisplay($order),
        ]);
    }

    public function mine()
    {
        return view('orders.mine', [
            'orders' => $this->orderQueryService->paginatedForUser((int) auth()->id()),
        ]);
    }

    public function myOrder(Order $order)
    {
        try {
            $order = $this->orderQueryService->loadOwnedOrder($order, (int) auth()->id());
        } catch (AuthorizationException) {
            abort(403);
        }

        return view('orders.show', compact('order'));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $this->orderService->updateStatus($order, $request->status());

        return back()->with('success', 'Estado del pedido actualizado.');
    }
}
