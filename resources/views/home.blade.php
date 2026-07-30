<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Pedidos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="row align-items-center min-vh-100">
            <div class="col-12 col-lg-6">
                <h1 class="display-5 fw-semibold mb-3">Sistema de Pedidos</h1>
                <p class="lead text-secondary mb-4">Gestiona productos, carrito, pedidos y usuarios desde un panel simple.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('catalog.index') }}" class="btn btn-primary">
                        <i class="bi bi-shop me-1"></i>
                        Ver catalogo
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Iniciar sesion
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-person-plus me-1"></i>
                        Crear cuenta
                    </a>
                </div>
            </div>
            <div class="col-12 col-lg-5 offset-lg-1 mt-4 mt-lg-0">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="bi bi-receipt-cutoff display-5 text-primary"></i>
                            <div>
                                <h2 class="h4 mb-1">Flujo completo</h2>
                                <p class="text-secondary mb-0">Catalogo, carrito y pedidos en una sola practica.</p>
                            </div>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0"><i class="bi bi-check2 text-success me-2"></i>Gestion de productos</li>
                            <li class="list-group-item px-0"><i class="bi bi-check2 text-success me-2"></i>Usuarios activos e inactivos</li>
                            <li class="list-group-item px-0"><i class="bi bi-check2 text-success me-2"></i>Estados de pedidos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
