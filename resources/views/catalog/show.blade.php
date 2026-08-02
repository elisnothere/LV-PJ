@extends('app')

@section('title', 'Sistema - Producto')
@section('page-title', $product->name)

@section('contenido')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revise los datos ingresados.</strong>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                @if ($product->primary_image_url)
                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="card-img-top" style="height: 380px; object-fit: cover;">
                @else
                    <div class="bg-body-secondary d-flex align-items-center justify-content-center" style="height: 380px;">
                        <i class="bi bi-box-seam display-1 text-secondary"></i>
                    </div>
                @endif

                @if ($product->images->count() > 1)
                    <div class="card-body border-top">
                        <div class="row g-2">
                            @foreach ($product->images as $image)
                                <div class="col-4 col-md-3">
                                    <img src="{{ $image->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded border" style="height: 90px; width: 100%; object-fit: cover;">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge text-bg-info">{{ $product->category?->name ?? 'Sin categoria' }}</span>
                        <span class="badge {{ $product->stock > 0 ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $product->stock > 0 ? 'Disponible' : 'Sin stock' }}
                        </span>
                    </div>

                    <div>
                        <h2 class="mb-2">{{ $product->name }}</h2>
                        <p class="text-secondary mb-0">{{ $product->description ?: 'Sin descripcion.' }}</p>
                    </div>

                    <div>
                        <p class="fs-3 fw-semibold mb-1">${{ number_format($product->price, 2) }}</p>
                        <p class="text-secondary mb-0">Stock actual: {{ $product->stock }}</p>
                    </div>

                    @if ($product->stock > 0)
                        <form action="{{ route('cart.add', $product) }}" method="post" class="d-flex flex-column flex-sm-row gap-2 mt-auto">
                            @csrf
                            <input type="number" name="quantity" class="form-control" value="1" min="1" max="{{ $product->stock }}" style="max-width: 120px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cart-plus me-1"></i>
                                Agregar al carrito
                            </button>
                        </form>
                    @else
                        <div class="mt-auto border rounded p-3 bg-light-subtle">
                            <h3 class="h5 mb-2">Avisame cuando vuelva a tener stock</h3>
                            <p class="text-secondary mb-3">Deja tu correo y te enviaremos un email apenas este producto vuelva a estar disponible.</p>
                            <form action="{{ route('product-stock-subscriptions.store', $product) }}" method="post" class="row g-2">
                                @csrf
                                <div class="col-12 col-md-8">
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', auth()->user()?->email ?? '') }}" placeholder="tu-correo@ejemplo.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12 col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">Quiero aviso</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
