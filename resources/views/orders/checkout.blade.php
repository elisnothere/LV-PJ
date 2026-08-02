@extends('app')

@section('title', 'Sistema - Realizar pedido')
@section('page-title', 'Realizar pedido')

@section('contenido')
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title">Envio</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('orders.checkout') }}" method="get" class="row g-3 align-items-end">
                        <div class="col-12 col-md-8">
                            <label for="shipping_city_id" class="form-label">Ciudad de envio</label>
                            <select id="shipping_city_id" name="shipping_city_id" class="form-select">
                                <option value="">Seleccione una ciudad</option>
                                @foreach ($shippingCities as $shippingCity)
                                    <option value="{{ $shippingCity->id }}" @selected(old('shipping_city_id', $selectedShippingCity?->id) == $shippingCity->id)>
                                        {{ $shippingCity->name }} - ${{ number_format($shippingCity->shipping_cost, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shipping_city_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                Actualizar envio
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos de entrega</h3>
                </div>
                <form action="{{ route('orders.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="shipping_city_id" value="{{ old('shipping_city_id', $selectedShippingCity?->id) }}">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>Revise los datos ingresados.</strong>
                                @error('cart')
                                    <div>{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Nombre</label>
                            <input type="text" class="form-control @error('customer_name') is-invalid @enderror" id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}">
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Correo</label>
                            <input type="email" class="form-control @error('customer_email') is-invalid @enderror" id="customer_email" name="customer_email" value="{{ old('customer_email', auth()->user()->email) }}">
                            @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">Telefono</label>
                            <input type="text" class="form-control @error('customer_phone') is-invalid @enderror" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}">
                            @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">Direccion de entrega</label>
                            <textarea class="form-control @error('delivery_address') is-invalid @enderror" id="delivery_address" name="delivery_address" rows="4">{{ old('delivery_address') }}</textarea>
                            @error('delivery_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" @disabled(empty($cart))>
                            <i class="bi bi-check2-circle me-1"></i>
                            Confirmar pedido
                        </button>
                        <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary ms-2">Volver al carrito</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Resumen</h3>
                </div>
                <div class="card-body">
                    @forelse ($cart as $item)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                @include('partials.product-image', [
                                    'imageUrl' => $item['image_url'] ?? null,
                                    'alt' => $item['name'],
                                    'size' => '56px',
                                    'iconClass' => 'bi-box-seam',
                                ])
                                <div>
                                    <span>{{ $item['quantity'] }} x {{ $item['name'] }}</span>
                                    @if (($item['regular_price'] ?? $item['price']) > $item['price'])
                                        <div class="small text-secondary text-decoration-line-through">${{ number_format($item['regular_price'], 2) }}</div>
                                    @endif
                                </div>
                            </div>
                            <strong>${{ number_format($item['price'] * $item['quantity'], 2) }}</strong>
                        </div>
                    @empty
                        <p class="mb-0">El carrito esta vacio.</p>
                    @endforelse
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <strong>${{ number_format($subtotal, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Envio</span>
                        <strong>${{ number_format($shippingCost, 2) }}</strong>
                    </div>
                    @if ($selectedShippingCity)
                        <div class="small text-muted mb-2">Ciudad seleccionada: {{ $selectedShippingCity->name }}</div>
                    @endif
                    <div class="d-flex justify-content-between">
                        <span>Total</span>
                        <strong>${{ number_format($total, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
