<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardService
{
    public function summary(): array
    {
        return [
            'productsCount' => Product::count(),
            'activeProductsCount' => Product::where('active', true)->count(),
            'pendingOrdersCount' => Order::where('status', 'pendiente')->count(),
            'ordersTotal' => Order::sum('total'),
            'usersCount' => User::count(),
            'latestOrders' => Order::latest()->take(5)->get(),
        ];
    }
}
