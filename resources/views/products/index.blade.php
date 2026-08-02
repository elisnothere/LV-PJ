@extends('app')

@section('title', 'Sistema - Productos')
@section('page-title', 'Mantenimiento de Productos')

@section('contenido')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Listado de productos</h3>
            <a href="{{ route('products.create') }}" class="btn btn-primary ms-auto">
                <i class="bi bi-plus-lg me-1"></i>
                Nuevo producto
            </a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 88px;">Foto</th>
                        <th>Producto</th>
                        <th>Categoria</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                @if ($product->primary_image_url)
                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="img-thumbnail" style="width: 56px; height: 56px; object-fit: cover;">
                                @else
                                    <div class="bg-body-secondary d-flex align-items-center justify-content-center rounded text-secondary" style="width: 56px; height: 56px;">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                {{ $product->name }}
                                @if ($product->hasActivePromotion())
                                    <div><span class="badge text-bg-danger mt-1">Promo activa</span></div>
                                @endif
                            </td>
                            <td>{{ $product->category?->name ?? 'Sin categoria' }}</td>
                            <td>
                                @if ($product->hasActivePromotion())
                                    <div class="fw-semibold text-danger">${{ number_format($product->effective_price, 2) }}</div>
                                    <small class="text-decoration-line-through text-secondary">${{ number_format($product->price, 2) }}</small>
                                @else
                                    ${{ number_format($product->price, 2) }}
                                @endif
                            </td>
                            <td>{{ $product->stock }}</td>
                            <td>
                                <span class="badge {{ $product->active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $product->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('products.destroy', $product) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar producto?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No hay productos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $products->links() }}
        </div>
    </div>
@endsection
