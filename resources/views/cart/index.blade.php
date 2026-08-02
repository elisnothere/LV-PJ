@extends('app')

@section('title', 'Sistema - Carrito')
@section('page-title', 'Carrito de pedidos')

@section('contenido')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revise la cantidad solicitada.</strong>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Productos seleccionados</h3>
            @if ($cart)
                <form action="{{ route('cart.clear') }}" method="post" class="ms-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i>
                        Vaciar
                    </button>
                </form>
            @endif
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 96px;">Foto</th>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th style="width: 180px;">Cantidad</th>
                        <th>Subtotal</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cart as $item)
                        <tr>
                            <td>
                                @if (! empty($item['image_url']))
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="img-thumbnail" style="width: 64px; height: 64px; object-fit: cover;">
                                @else
                                    <div class="bg-body-secondary d-flex align-items-center justify-content-center rounded text-secondary" style="width: 64px; height: 64px;">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $item['name'] }}</td>
                            <td>
                                @if (($item['regular_price'] ?? $item['price']) > $item['price'])
                                    <div class="fw-semibold text-danger">${{ number_format($item['price'], 2) }}</div>
                                    <small class="text-decoration-line-through text-secondary">${{ number_format($item['regular_price'], 2) }}</small>
                                @else
                                    ${{ number_format($item['price'], 2) }}
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('cart.update', $item['id']) }}" method="post" class="d-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" class="form-control form-control-sm" value="{{ $item['quantity'] }}" min="1" max="{{ $item['stock'] }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td>${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                            <td class="text-end">
                                <form action="{{ route('cart.remove', $item['id']) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">El carrito esta vacio.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-column flex-lg-row gap-3 align-items-lg-center">
            <div>
                <div><strong>Subtotal:</strong> ${{ number_format($subtotal, 2) }}</div>
                <div>
                    <strong>Envio:</strong>
                    @if ($selectedShippingCity)
                        ${{ number_format($shippingCost, 2) }}
                        <span class="text-muted">({{ $selectedShippingCity->name }})</span>
                    @else
                        <span class="text-muted">Se calcula en checkout</span>
                    @endif
                </div>
                <div><strong>Total:</strong> ${{ number_format($total, 2) }}</div>
            </div>
            <div class="ms-lg-auto">
                <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary">Seguir comprando</a>
                <a href="{{ route('orders.checkout') }}" class="btn btn-primary {{ empty($cart) ? 'disabled' : '' }}">
                    <i class="bi bi-bag-check me-1"></i>
                    Realizar pedido
                </a>
            </div>
        </div>
    </div>
@endsection
