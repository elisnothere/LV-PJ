<?php

namespace App\Http\Controllers;

use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderQueryService;
use App\Services\OrderService;
use App\Services\ShippingCityService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private OrderQueryService $orderQueryService,
        private ShippingCityService $shippingCityService,
    ) {
    }

    public function checkout(Request $request)
    {
        if ($request->exists('shipping_city_id')) {
            $selectedId = $request->filled('shipping_city_id')
                ? (int) $request->integer('shipping_city_id')
                : null;

            $this->cartService->setShippingCityId($selectedId);
        }

        $selectedShippingCity = $this->cartService->selectedShippingCity();

        if ($this->cartService->selectedShippingCityId() && ! $selectedShippingCity) {
            $this->cartService->setShippingCityId(null);
        }

        return view('orders.checkout', [
            'cart' => $this->cartService->contents(),
            'shippingCities' => $this->shippingCityService->active(),
            'selectedShippingCity' => $selectedShippingCity,
            'subtotal' => $this->cartService->subtotal(),
            'shippingCost' => $this->cartService->shippingCost(),
            'total' => $this->cartService->totalWithShipping(),
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createFromCart(
                $this->cartService->contents(),
                $request->orderData(),
                auth()->id(),
                $request->shippingCityId(),
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
