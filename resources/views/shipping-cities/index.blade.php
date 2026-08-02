@extends('app')

@section('title', 'Sistema - Ciudades de envio')
@section('page-title', 'Ciudades de envio')

@section('contenido')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Listado de ciudades</h3>
            <form action="{{ route('shipping-cities.index') }}" method="get" class="ms-auto d-flex gap-2" style="max-width: 420px;">
                <input type="search" name="buscar" class="form-control form-control-sm" value="{{ request('buscar') }}" placeholder="Buscar ciudad">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
            <a href="{{ route('shipping-cities.create') }}" class="btn btn-sm btn-success ms-2">
                <i class="bi bi-plus-lg me-1"></i>
                Nueva
            </a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ciudad</th>
                        <th>Costo de envio</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shippingCities as $shippingCity)
                        <tr>
                            <td>{{ $shippingCity->name }}</td>
                            <td>${{ number_format($shippingCity->shipping_cost, 2) }}</td>
                            <td>
                                <span class="badge {{ $shippingCity->active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $shippingCity->active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('shipping-cities.edit', $shippingCity) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('shipping-cities.active', $shippingCity) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <i class="bi {{ $shippingCity->active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">No hay ciudades de envio registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $shippingCities->links() }}
        </div>
    </div>
@endsection
