@extends('app')

@section('title', 'Sistema - Catalogo')
@section('page-title', 'Catalogo de productos Online')

@push('css')
    <style>
        .catalog-pagination {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .catalog-pagination nav > div:first-child {
            display: none;
        }

        .catalog-pagination nav > div:last-child {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .catalog-pagination .pagination {
            gap: .35rem;
            flex-wrap: wrap;
            margin-bottom: 0;
        }

        .catalog-pagination .page-item .page-link,
        .catalog-pagination .page-item span {
            min-width: 2.5rem;
            padding: .45rem .75rem;
            border-radius: .65rem;
            font-size: .95rem;
            line-height: 1.2;
            text-align: center;
            box-shadow: none;
        }

        .catalog-pagination .page-item.active .page-link,
        .catalog-pagination .page-item.active span {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .catalog-pagination .page-item.disabled .page-link,
        .catalog-pagination .page-item.disabled span {
            opacity: .55;
        }

        @media (max-width: 576px) {
            .catalog-pagination .page-item .page-link,
            .catalog-pagination .page-item span {
                min-width: 2.2rem;
                padding: .4rem .6rem;
                font-size: .9rem;
            }
        }
    </style>
@endpush
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
                            <option value="{{ $category->slug }}" @selected(request('categoria') === $category->slug || request('categoria') === $category->name)>{{ $category->name }}</option>
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
                    <a href="{{ route('catalog.show', $product) }}" class="text-decoration-none text-reset">
                        @include('partials.product-image', [
                            'imageUrl' => $product->primary_image_url,
                            'alt' => $product->name,
                            'imageClass' => 'card-img-top',
                            'style' => 'height: 180px; width: 100%; object-fit: cover;',
                        ])
                    </a>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between gap-2">
                            <h5 class="card-title mb-0"><a href="{{ route('catalog.show', $product) }}" class="text-decoration-none text-reset">{{ $product->name }}</a></h5>
                            <span class="badge text-bg-info align-self-start">{{ $product->category?->name ?? 'Sin categoria' }}</span>
                        </div>
                        <p class="card-text text-secondary mt-2">{{ $product->description ?: 'Sin descripcion.' }}</p>
                        <div class="mt-auto">
                            @if ($product->hasActivePromotion())
                                <p class="fs-5 fw-semibold mb-0 text-danger">${{ number_format($product->effective_price, 2) }}</p>
                                <p class="text-secondary text-decoration-line-through mb-1">${{ number_format($product->price, 2) }}</p>
                            @else
                                <p class="fs-5 fw-semibold mb-1">${{ number_format($product->price, 2) }}</p>
                            @endif
                            <p class="text-secondary mb-3">Stock: {{ $product->stock }}</p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('catalog.show', $product) }}" class="btn btn-outline-secondary">Ver detalle</a>
                                <form action="{{ route('cart.add', $product) }}" method="post" class="d-flex gap-2 flex-grow-1">
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
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">No se encontraron productos.</div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="catalog-pagination">
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
@endsection
