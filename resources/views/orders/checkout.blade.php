@extends('app')

@section('title', 'Sistema - Realizar pedido')
@section('page-title', 'Realizar pedido')

@section('contenido')
    <div class="row">
        <div class="col-12 col-lg-7">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos de entrega</h3>
                </div>
                <form action="{{ route('orders.store') }}" method="post">
                    @csrf
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
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ $item['quantity'] }} x {{ $item['name'] }}</span>
                            <strong>${{ number_format($item['price'] * $item['quantity'], 2) }}</strong>
                        </div>
                    @empty
                        <p class="mb-0">El carrito esta vacio.</p>
                    @endforelse
                </div>
                <div class="card-footer">
                    <strong>Total: ${{ number_format($total, 2) }}</strong>
                </div>
            </div>
        </div>
    </div>
@endsection
