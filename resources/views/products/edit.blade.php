@extends('app')

@section('title', 'Sistema - Editar producto')
@section('page-title', 'Editar producto')

@section('contenido')
    <div class="row">
        <div class="col-12 col-lg-9">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos del producto</h3>
                </div>
                <form action="{{ route('products.update', $product) }}" method="post" enctype="multipart/form-data">
                    @method('PUT')
                    @include('products.form')
                </form>
            </div>
        </div>
    </div>
@endsection
