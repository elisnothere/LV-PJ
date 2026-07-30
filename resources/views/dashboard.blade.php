@extends('app')

@section('title', 'Sistema - Dashboard')
@section('page-title', 'Dashboard')

@section('contenido')
    <div class="row">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $productsCount }}</h3>
                    <p>Productos registrados</p>
                </div>
                <i class="small-box-icon bi bi-box-seam"></i>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>{{ $activeProductsCount }}</h3>
                    <p>Productos activos</p>
                </div>
                <i class="small-box-icon bi bi-check-circle"></i>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ $pendingOrdersCount }}</h3>
                    <p>Pedidos pendientes</p>
                </div>
                <i class="small-box-icon bi bi-hourglass-split"></i>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>{{ $usersCount }}</h3>
                    <p>Usuarios</p>
                </div>
                <i class="small-box-icon bi bi-people"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ultimos pedidos</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestOrders as $order)
                                <tr>
                                    <td><a href="{{ route('orders.show', $order) }}">{{ $order->code }}</a></td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td><span class="badge text-bg-primary">{{ ucfirst($order->status) }}</span></td>
                                    <td>${{ number_format($order->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">No hay pedidos todavia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Resumen de ventas</h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary mb-1">Total acumulado</p>
                    <p class="display-6 fw-semibold mb-0">${{ number_format($ordersTotal, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
