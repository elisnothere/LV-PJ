@extends('app')

@section('title', 'Sistema - Pedido')
@section('page-title', 'Pedido ' . $order->code)

@section('contenido')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detalle del pedido</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>
                                        <div>${{ number_format($item->unit_price, 2) }}</div>
                                        @if ($item->regular_unit_price && $item->regular_unit_price > $item->unit_price)
                                            <small class="text-secondary text-decoration-line-through">${{ number_format($item->regular_unit_price, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>${{ number_format($item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-end">
                    <div><strong>Subtotal:</strong> ${{ number_format((float) ($order->subtotal ?? $order->total), 2) }}</div>
                    <div><strong>Envio:</strong> ${{ number_format((float) ($order->shipping_cost ?? 0), 2) }}</div>
                    <div><strong>Total:</strong> ${{ number_format($order->total, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cliente</h3>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Nombre:</strong> {{ $order->customer_name }}</p>
                    <p class="mb-1"><strong>Correo:</strong> {{ $order->customer_email }}</p>
                    <p class="mb-1"><strong>Telefono:</strong> {{ $order->customer_phone ?: 'No registrado' }}</p>
                    <p class="mb-1"><strong>Direccion:</strong> {{ $order->delivery_address }}</p>
                    <p class="mb-0"><strong>Ciudad de envio:</strong> {{ $order->shipping_city_name ?: 'No registrada' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Estado del pedido</h3>
                </div>
                @if (auth()->user()->role === 'admin')
                    <form action="{{ route('orders.status', $order) }}" method="post">
                        @csrf
                        @method('PATCH')
                        <div class="card-body">
                            <select name="status" class="form-select">
                                @foreach (\App\Models\Order::STATUSES as $status)
                                    <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                Actualizar estado
                            </button>
                        </div>
                    </form>
                @else
                    <div class="card-body">
                        <span class="badge text-bg-primary fs-6">{{ ucfirst($order->status) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
