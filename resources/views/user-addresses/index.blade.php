@extends('layouts.storefront')

@section('title', 'Sistema - Mis direcciones')
@section('page-title', 'Mis direcciones')
@section('page-subtitle', 'Guarda varias direcciones de entrega y elige la que usaras al momento de comprar.')

@section('contenido')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">Direcciones guardadas</h3>
            <a href="{{ route('addresses.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                Nueva direccion
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @forelse ($addresses as $address)
                    <div class="col-12 col-lg-6">
                        <div class="border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $address->primary_address }}</div>
                                    @if ($address->secondary_address)
                                        <div class="text-secondary">{{ $address->secondary_address }}</div>
                                    @endif
                                    <div class="small text-muted mt-2">
                                        {{ $address->shippingCity->name }}
                                        <span class="mx-1">·</span>
                                        Envio: ${{ number_format($address->shippingCity->shipping_cost, 2) }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a href="{{ route('addresses.edit', $address) }}" class="btn btn-sm btn-outline-primary mb-2">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('addresses.destroy', $address) }}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            Todavia no tienes direcciones guardadas. Crea una para poder usarla en checkout.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
