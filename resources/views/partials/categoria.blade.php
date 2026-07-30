@extends('app')

@section('title', 'Sistema - Categorias')
@section('page-title', 'Categorias')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/mantenimiento.css') }}">
@endpush

@section('contenido')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado de categorias</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @forelse ($categories as $category)
                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                        <a href="{{ route('catalog.index', ['categoria' => $category->category]) }}" class="text-decoration-none">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-tag fs-3 text-primary"></i>
                                    <div>
                                        <h4 class="h5 mb-1">{{ $category->category }}</h4>
                                        <p class="text-secondary mb-0">{{ $category->products_count }} productos</p>
                                    </div>
                                </div>
                            </div>
                        </a>
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
