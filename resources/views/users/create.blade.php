@extends('app')

@section('title', 'Sistema - Nuevo usuario')
@section('page-title', 'Crear usuario')

@section('contenido')
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos del usuario</h3>
                </div>
                <form action="{{ route('usuarios.store') }}" method="post">
                    @include('users.form')
                </form>
            </div>
        </div>
    </div>
@endsection
