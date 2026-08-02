@extends('app')

@section('title', 'Sistema - Editar ciudad de envio')
@section('page-title', 'Editar ciudad de envio')

@section('contenido')
    <div class="row">
        <div class="col-12 col-lg-7">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos de la ciudad</h3>
                </div>
                <form action="{{ route('shipping-cities.update', $shippingCity) }}" method="post">
                    @method('PUT')
                    @include('shipping-cities.form', ['shippingCity' => $shippingCity])
                </form>
            </div>
        </div>
    </div>
@endsection
