@extends('layouts.storefront')

@section('title', 'Sistema - Nueva direccion')
@section('page-title', 'Nueva direccion')
@section('page-subtitle', 'Guarda una nueva direccion de entrega vinculada a una ciudad disponible para envio.')

@section('contenido')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Agregar direccion</h3>
                </div>
                <form action="{{ route('addresses.store') }}" method="post">
                    @include('user-addresses.form')
                </form>
            </div>
        </div>
    </div>
@endsection
