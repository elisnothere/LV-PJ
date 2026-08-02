@extends('app')

@section('title', 'Sistema - Categorias')
@section('page-title', 'Categorias')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/mantenimiento.css') }}">
@endpush

@section('contenido')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">Listado de categorias</h3>
            @auth
                @if (auth()->user()->role === 'admin')
                    <span class="badge text-bg-primary">Modo administrador</span>
                @endif
            @endauth
        </div>
        <div class="card-body">
            @auth
                @if (auth()->user()->role === 'admin')
                    <form action="{{ route('categories.store') }}" method="post" class="row g-2 align-items-end mb-4 pb-3 border-bottom">
                        @csrf
                        <div class="col-12 col-md-8 col-lg-9">
                            <label for="category_create_name" class="form-label">Nueva categoria</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="category_create_name" name="name" value="{{ old('name') }}" placeholder="Ej.: Hogar inteligente">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i>
                                Crear categoria
                            </button>
                        </div>
                    </form>
                @endif
            @endauth

            <div class="row">
                @forelse ($categories as $category)
                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}" class="text-decoration-none d-block mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-tag fs-3 text-primary"></i>
                                    <div>
                                        <h4 class="h5 mb-1">{{ $category->name }}</h4>
                                        <p class="text-secondary mb-0">{{ $category->products_count }} productos</p>
                                    </div>
                                </div>
                            </a>

                            @auth
                                @if (auth()->user()->role === 'admin')
                                    <form action="{{ route('categories.update', $category) }}" method="post" class="border-top pt-3">
                                        @csrf
                                        @method('PATCH')
                                        <label for="category_name_{{ $category->id }}" class="form-label small text-secondary">Editar nombre</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="category_name_{{ $category->id }}" name="name" value="{{ old('name', $category->name) }}">
                                            <button type="submit" class="btn btn-outline-primary">Guardar</button>
                                        </div>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="mb-0">No hay categorias disponibles.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
