@extends('layouts.storefront')

@section('title', 'Sistema - Realizar pedido')
@section('page-title', 'Realizar pedido')
@section('page-subtitle', 'Confirma tu envio y revisa el resumen final antes de completar la compra.')

@section('contenido')
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card card-primary card-outline mb-4">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <h3 class="card-title mb-0">Envio</h3>
                    <a href="{{ route('addresses.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg me-1"></i>
                        Nueva direccion
                    </a>
                </div>
                <div class="card-body">
                    @if ($addresses->isEmpty())
                        <div class="alert alert-warning mb-0">
                            Necesitas guardar al menos una direccion para continuar con la compra.
                            <a href="{{ route('addresses.create') }}" class="alert-link">Crear direccion</a>
                        </div>
                    @else
                        <form action="{{ route('orders.checkout') }}" method="get" class="row g-3 align-items-end">
                            <div class="col-12 col-md-8">
                                <label for="user_address_id" class="form-label">Direccion guardada</label>
                                <select id="user_address_id" name="user_address_id" class="form-select @error('user_address_id') is-invalid @enderror">
                                    <option value="">Seleccione una direccion</option>
                                    @foreach ($addresses as $address)
                                        <option value="{{ $address->id }}" @selected(old('user_address_id', $selectedAddress?->id) == $address->id)>
                                            {{ $address->formattedAddress() }} ({{ $address->shippingCity->name }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_address_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Actualizar envio
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos de entrega</h3>
                </div>
                <form action="{{ route('orders.store') }}" method="post">
                    @csrf
                    @if ($selectedAddress)
                        <input type="hidden" name="user_address_id" value="{{ $selectedAddress->id }}">
                    @elseif ($selectedShippingCity)
                        <input type="hidden" name="shipping_city_id" value="{{ $selectedShippingCity->id }}">
                    @endif
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
                            <label class="form-label">Direccion seleccionada</label>
                            @if ($selectedAddress)
                                <div class="border rounded p-3 bg-body-tertiary">
                                    <div class="fw-semibold">{{ $selectedAddress->primary_address }}</div>
                                    @if ($selectedAddress->secondary_address)
                                        <div class="text-secondary">{{ $selectedAddress->secondary_address }}</div>
                                    @endif
                                    <div class="small text-muted mt-2">{{ $selectedAddress->shippingCity->name }}</div>
                                </div>
                            @elseif ($selectedShippingCity)
                                <div class="border rounded p-3 bg-body-tertiary">
                                    <div class="fw-semibold">Direccion legacy ingresada manualmente</div>
                                    <div class="small text-muted mt-2">{{ $selectedShippingCity->name }}</div>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">Selecciona una direccion guardada para continuar.</div>
                            @endif
                        </div>

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
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" @disabled(empty($cart) || (! $selectedAddress && ! $selectedShippingCity))>
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
                    @if ($selectedAddress)
                        <div class="small text-muted mb-2">Direccion seleccionada: {{ $selectedAddress->formattedAddress() }}</div>
                        <div class="small text-muted mb-2">Ciudad seleccionada: {{ $selectedAddress->shippingCity->name }}</div>
                    @elseif ($selectedShippingCity)
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
