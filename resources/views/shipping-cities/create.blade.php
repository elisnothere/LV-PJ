@extends('app')

@section('title', 'Sistema - Nueva ciudad de envio')
@section('page-title', 'Nueva ciudad de envio')

@section('contenido')
    <div class="row">
        <div class="col-12 col-lg-7">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos de la ciudad</h3>
                </div>
                <form action="{{ route('shipping-cities.store') }}" method="post">
                    @include('shipping-cities.form')
                </form>
            </div>
        </div>
    </div>
@endsection
