<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'productsCount' => Product::count(),
            'activeProductsCount' => Product::where('active', true)->count(),
            'pendingOrdersCount' => Order::where('status', 'pendiente')->count(),
            'ordersTotal' => Order::sum('total'),
            'usersCount' => User::count(),
            'latestOrders' => Order::latest()->take(5)->get(),
        ]);
    }
}
