<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        if (! $order->status) {
            return;
        }

        $order->statusHistory()->create([
            'status' => $order->status,
            'assigned_at' => $order->created_at ?? now(),
        ]);
    }

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $order->statusHistory()->create([
            'status' => $order->status,
            'assigned_at' => $order->updated_at ?? now(),
        ]);
    }
}
