@extends('app')

@section('title', 'Sistema - Catalogo')
@section('page-title', 'Catalogo de productos Online')

@section('contenido')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revise la cantidad solicitada.</strong>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('catalog.index') }}" method="get" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="buscar" class="form-label">Buscar producto</label>
                    <input type="search" class="form-control" id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o descripcion">
                </div>
                <div class="col-12 col-md-4">
                    <label for="categoria" class="form-label">Categoria</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(request('categoria') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @forelse ($products as $product)
            <div class="col-12 col-md-6 col-xl-4 mb-3">
                <div class="card h-100">
                    @if ($product->primary_image_url)
                        <img src="{{ $product->primary_image_url }}" class="card-img-top" alt="{{ $product->name }}" style="height: 180px; object-fit: cover;">
                    @else
                        <div class="bg-body-secondary d-flex align-items-center justify-content-center" style="height: 180px;">
                            <i class="bi bi-box-seam display-3 text-secondary"></i>
                        </div>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between gap-2">
                            <h5 class="card-title">{{ $product->name }}</h5>
                            <span class="badge text-bg-info align-self-start">{{ $product->category }}</span>
                        </div>
                        <p class="card-text text-secondary">{{ $product->description ?: 'Sin descripcion.' }}</p>
                        <div class="mt-auto">
                            <p class="fs-5 fw-semibold mb-1">${{ number_format($product->price, 2) }}</p>
                            <p class="text-secondary mb-3">Stock: {{ $product->stock }}</p>
                            <form action="{{ route('cart.add', $product) }}" method="post" class="d-flex gap-2">
                                @csrf
                                <input type="number" name="quantity" class="form-control" value="1" min="1" max="{{ max($product->stock, 1) }}" style="max-width: 90px;">
                                <button type="submit" class="btn btn-primary flex-grow-1" @disabled($product->stock < 1)>
                                    <i class="bi bi-cart-plus me-1"></i>
                                    Agregar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">No se encontraron productos.</div>
                </div>
            </div>
        @endforelse
    </div>

    {{ $products->links() }}
@endsection
