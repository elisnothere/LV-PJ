@extends('app')

@section('title', 'Sistema - Crear usuario')
@section('page-title', 'Crear usuario')

@section('contenido')
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Registro de usuarios</h3>
                </div>

                <form action="{{ route('usuarios.store') }}" method="post">
                    @csrf

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>Revise los datos ingresados.</strong>
                            </div>
                        @endif

                        <div class="row">
                            <div class="form-group col-12 col-md-6 mb-3">
                                <label for="name">Nombre</label>
                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="Ingrese el nombre"
                                >
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-12 col-md-6 mb-3">
                                <label for="email">Correo</label>
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="Ingrese el correo"
                                >
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-12 col-md-6 mb-3">
                                <label for="password">Contrasena</label>
                                <input
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    placeholder="Ingrese la contrasena"
                                >
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-12 col-md-6 mb-3">
                                <label for="password_confirmation">Confirmar contrasena</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    placeholder="Confirme la contrasena"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>
                            Guardar
                        </button>
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
