@extends('layouts.storefront')

@section('title', 'Sistema - Editar direccion')
@section('page-title', 'Editar direccion')
@section('page-subtitle', 'Actualiza tus datos de entrega y la ciudad usada para calcular el envio.')

@section('contenido')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Editar direccion</h3>
                </div>
                <form action="{{ route('addresses.update', $address) }}" method="post">
                    @method('PUT')
                    @include('user-addresses.form')
                </form>
            </div>
        </div>
    </div>
@endsection
