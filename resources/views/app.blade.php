<!doctype html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#0d6efd" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <title>@yield('title', 'Sistema')</title>

    <link rel="preload" href="{{ asset('css/adminlte.min.css') }}" as="style" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        crossorigin="anonymous"
        media="print"
        onload="this.media = 'all'"
    />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
        crossorigin="anonymous"
    />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous"
    />
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}" />
    @stack('css')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="{{ url('/') }}" class="nav-link">Inicio</a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="{{ url('/contacto') }}" class="nav-link">Contacto</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
                        </a>
                    </li>
                    <li class="nav-item dropdown user-menu">
                        @auth
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <img
                                    src="{{ asset('assets/img/user2-160x160.jpg') }}"
                                    class="user-image rounded-circle shadow"
                                    alt="Usuario"
                                />
                                <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                                <li class="user-header text-bg-primary">
                                    <img
                                        src="{{ asset('assets/img/user2-160x160.jpg') }}"
                                        class="rounded-circle shadow"
                                        alt="Usuario"
                                    />
                                    <p>
                                        {{ auth()->user()->name }}
                                        <small>{{ ucfirst(auth()->user()->role) }}</small>
                                    </p>
                                </li>
                                <li class="user-footer">
                                    <a href="{{ auth()->user()->role === 'admin' ? route('dashboard') : route('catalog.index') }}" class="btn btn-outline-secondary">Inicio</a>
                                    <form action="{{ route('logout') }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger float-end">Salir</button>
                                    </form>
                                </li>
                            </ul>
                        @else
                            <a href="{{ route('login') }}" class="nav-link">
                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                Iniciar sesion
                            </a>
                        @endauth
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ route('home') }}" class="brand-link">
                    <img
                        src="{{ asset('assets/img/AdminLTELogo.png') }}"
                        alt="AdminLTE Logo"
                        class="brand-image opacity-75 shadow"
                    />
                    <span class="brand-text fw-light">Sistema</span>
                </a>
            </div>

            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul
                        class="nav sidebar-menu flex-column"
                        data-lte-toggle="treeview"
                        role="navigation"
                        aria-label="Menu principal"
                        data-accordion="false"
                        id="navigation"
                    >
                        <li class="nav-item">
                            <a href="{{ route('home') }}" class="nav-link {{ request()->is('/') || request()->is('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-speedometer"></i>
                                <p>Inicio</p>
                            </a>
                        </li>
                        @if (auth()->check() && auth()->user()->role === 'admin')
                            <li class="nav-item">
                                <a href="{{ route('usuarios.index') }}" class="nav-link {{ request()->is('usuarios*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-people"></i>
                                    <p>Usuarios</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ url('/categoria') }}" class="nav-link {{ request()->is('categoria') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-tags"></i>
                                <p>Categorias</p>
                            </a>
                        </li>
                        @if (auth()->check() && auth()->user()->role === 'admin')
                            <li class="nav-item">
                                <a href="{{ route('products.index') }}" class="nav-link {{ request()->is('products*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-box-seam"></i>
                                    <p>Productos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('shipping-cities.index') }}" class="nav-link {{ request()->is('shipping-cities*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-truck"></i>
                                    <p>Ciudades de envio</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('catalog.index') }}" class="nav-link {{ request()->is('catalogo*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-shop"></i>
                                <p>Catalogo</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('cart.index') }}" class="nav-link {{ request()->is('carrito') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-cart3"></i>
                                <p>Carrito</p>
                            </a>
                        </li>
                        @auth
                            <li class="nav-item">
                                <a href="{{ route('orders.mine') }}" class="nav-link {{ request()->is('mis-pedidos*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-bag-check"></i>
                                    <p>Mis pedidos</p>
                                </a>
                            </li>
                        @endauth
                        @if (auth()->check() && auth()->user()->role === 'admin')
                            <li class="nav-item">
                                <a href="{{ route('orders.index') }}" class="nav-link {{ request()->is('pedidos*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-receipt"></i>
                                    <p>Pedidos</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ url('/contacto') }}" class="nav-link {{ request()->is('contacto') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-envelope"></i>
                                <p>Contacto</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">@yield('page-title', 'Panel')</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    @yield('page-title', 'Panel')
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    @yield('contenido')
                </div>
            </div>
        </main>

        <footer class="app-footer">
            <div class="float-end d-none d-sm-inline">Panel administrativo</div>
            <strong>2026 &copy; Sistema.</strong> Todos los derechos reservados.
        </footer>
    </div>

    <script
        src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
        crossorigin="anonymous"
    ></script>
    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        crossorigin="anonymous"
    ></script>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
        crossorigin="anonymous"
    ></script>
    <script src="{{ asset('js/adminlte.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarWrapper = document.querySelector('.sidebar-wrapper');
            const isMobile = window.innerWidth <= 992;

            if (
                sidebarWrapper &&
                window.OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
                !isMobile
            ) {
                window.OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: 'os-theme-light',
                        autoHide: 'leave',
                        clickScroll: true,
                    },
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
