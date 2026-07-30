<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema - Crear cuenta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
</head>
<body class="register-page bg-body-tertiary">
    <div class="register-box">
        <div class="register-logo">
            <a href="{{ route('home') }}">Sistema de Pedidos</a>
        </div>
        <div class="card">
            <div class="card-body register-card-body">
                <p class="register-box-msg">Crear cuenta</p>

                @if ($errors->any())
                    <div class="alert alert-danger">Revise los datos ingresados.</div>
                @endif

                <form action="{{ route('register.store') }}" method="post">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nombre">
                        <div class="input-group-text"><span class="bi bi-person"></span></div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="Correo">
                        <div class="input-group-text"><span class="bi bi-envelope"></span></div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Contrasena">
                        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Confirmar contrasena">
                        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Crear cuenta</button>
                </form>

                <p class="mb-0 mt-3">
                    <a href="{{ route('login') }}">Ya tengo cuenta</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
