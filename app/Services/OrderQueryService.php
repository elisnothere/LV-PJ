<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderQueryService
{
    public function paginatedForAdmin(?string $search = null): LengthAwarePaginator
    {
        return Order::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function paginatedForUser(int $userId): LengthAwarePaginator
    {
        return Order::where('user_id', $userId)
            ->latest()
            ->paginate(10);
    }

    public function loadForDisplay(Order $order): Order
    {
        $order->load(['items.product.primaryImage']);

        return $order;
    }

    public function loadOwnedOrder(Order $order, int $userId): Order
    {
        if ($order->user_id !== $userId) {
            throw new AuthorizationException();
        }

        $order->load(['items.product.primaryImage']);

        return $order;
    }
}
