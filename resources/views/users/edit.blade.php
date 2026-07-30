@extends('app')

@section('title', 'Sistema - Editar usuario')
@section('page-title', 'Editar usuario')

@section('contenido')
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos del usuario</h3>
                </div>
                <form action="{{ route('usuarios.update', $user) }}" method="post">
                    @method('PUT')
                    @include('users.form')
                </form>
            </div>
        </div>
    </div>
@endsection
