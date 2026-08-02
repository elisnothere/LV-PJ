<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Storefront')</title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@fontsource/manrope@5.0.16/index.css"
        crossorigin="anonymous"
    />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous"
    />
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <style>
        :root {
            --store-bg: #f6f1e8;
            --store-surface: rgba(255, 255, 255, 0.82);
            --store-border: rgba(91, 78, 58, 0.12);
            --store-ink: #1f2933;
            --store-muted: #6b7280;
            --store-brand: #0f766e;
            --store-brand-deep: #115e59;
            --store-accent: #c2410c;
        }

        body.storefront-body {
            font-family: 'Manrope', sans-serif;
            color: var(--store-ink);
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.16), transparent 32%),
                radial-gradient(circle at top right, rgba(194, 65, 12, 0.16), transparent 24%),
                linear-gradient(180deg, #fffdf8 0%, var(--store-bg) 46%, #f8fafc 100%);
            min-height: 100vh;
        }

        .storefront-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .storefront-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(14px);
            background: rgba(255, 253, 248, 0.88);
            border-bottom: 1px solid var(--store-border);
        }

        .storefront-brand {
            color: var(--store-ink);
            font-weight: 800;
            letter-spacing: -.02em;
            text-decoration: none;
        }

        .storefront-brand-mark {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--store-brand), var(--store-accent));
            color: #fff;
            box-shadow: 0 0.75rem 1.5rem rgba(15, 118, 110, 0.18);
        }

        .storefront-nav-link {
            color: var(--store-muted);
            text-decoration: none;
            font-weight: 600;
            padding: .65rem .95rem;
            border-radius: 999px;
            transition: .2s ease;
        }

        .storefront-nav-link:hover,
        .storefront-nav-link.active {
            color: var(--store-brand-deep);
            background: rgba(15, 118, 110, 0.09);
        }

        .storefront-user-chip {
            border: 1px solid var(--store-border);
            background: rgba(255, 255, 255, 0.72);
            border-radius: 999px;
            padding: .3rem .45rem;
        }

        .storefront-pagehead {
            padding: 2rem 0 1.25rem;
        }

        .storefront-pagehead-card {
            border: 1px solid var(--store-border);
            border-radius: 1.5rem;
            background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.74));
            box-shadow: 0 1.2rem 3rem rgba(15, 23, 42, 0.08);
        }

        .storefront-kicker {
            color: var(--store-accent);
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .storefront-main {
            flex: 1;
            padding-bottom: 3rem;
        }

        .storefront-footer {
            border-top: 1px solid var(--store-border);
            background: rgba(255, 253, 248, 0.78);
        }

        .storefront-footer small {
            color: var(--store-muted);
        }
    </style>
    @stack('css')
</head>
<body class="storefront-body">
    @php
        $storefrontLinks = [
            ['label' => 'Catalogo', 'route' => route('catalog.index'), 'active' => request()->is('catalogo*')],
            ['label' => 'Categorias', 'route' => route('categories.index'), 'active' => request()->is('categoria')],
            ['label' => 'Contacto', 'route' => url('/contacto'), 'active' => request()->is('contacto')],
            ['label' => 'Carrito', 'route' => route('cart.index'), 'active' => request()->is('carrito')],
        ];

        if (auth()->check()) {
            $storefrontLinks[] = [
                'label' => 'Mis direcciones',
                'route' => route('addresses.index'),
                'active' => request()->is('mis-direcciones*'),
            ];
            $storefrontLinks[] = [
                'label' => 'Mis pedidos',
                'route' => route('orders.mine'),
                'active' => request()->is('mis-pedidos*'),
            ];
        }
    @endphp

    <div class="storefront-shell">
        <header class="storefront-header">
            <div class="container py-3">
                <div class="d-flex flex-wrap align-items-center gap-3 justify-content-between">
                    <a href="{{ route('catalog.index') }}" class="storefront-brand d-flex align-items-center gap-3">
                        <span class="storefront-brand-mark">
                            <i class="bi bi-bag-heart"></i>
                        </span>
                        <span>
                            <span class="d-block">Tienda online</span>
                            <small class="text-secondary fw-semibold">Compra y seguimiento de pedidos</small>
                        </span>
                    </a>

                    <nav class="d-none d-lg-flex align-items-center gap-2" aria-label="Navegacion storefront">
                        @foreach ($storefrontLinks as $link)
                            <a href="{{ $link['route'] }}" class="storefront-nav-link {{ $link['active'] ? 'active' : '' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="d-flex align-items-center gap-2">
                        @auth
                            <div class="dropdown">
                                <button class="btn storefront-user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i>
                                    {{ auth()->user()->name }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><span class="dropdown-item-text text-secondary small">{{ auth()->user()->email }}</span></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('addresses.index') }}">Mis direcciones</a></li>
                                    <li><a class="dropdown-item" href="{{ route('orders.mine') }}">Mis pedidos</a></li>
                                    <li>
                                        <form action="{{ route('logout') }}" method="post">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">Cerrar sesion</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary">Iniciar sesion</a>
                            <a href="{{ route('register') }}" class="btn btn-primary">Crear cuenta</a>
                        @endauth
                    </div>
                </div>

                <nav class="d-flex d-lg-none flex-wrap gap-2 pt-3" aria-label="Navegacion storefront movil">
                    @foreach ($storefrontLinks as $link)
                        <a href="{{ $link['route'] }}" class="storefront-nav-link {{ $link['active'] ? 'active' : '' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        <main class="storefront-main">
            <div class="container storefront-pagehead">
                <div class="storefront-pagehead-card p-4 p-lg-5">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-lg-8">
                            <div class="storefront-kicker">Storefront</div>
                            <h1 class="display-6 fw-bold mb-2">@yield('page-title', 'Tienda online')</h1>
                            <p class="text-secondary mb-0">@yield('page-subtitle', 'Explora productos, completa tu compra y revisa tus pedidos desde un entorno separado del panel administrativo.')</p>
                        </div>
                        <div class="col-12 col-lg-4 text-lg-end">
                            <a href="{{ route('catalog.index') }}" class="btn btn-primary">
                                <i class="bi bi-stars me-1"></i>
                                Ver catalogo
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container">
                @yield('contenido')
            </div>
        </main>

        <footer class="storefront-footer py-4">
            <div class="container d-flex flex-column flex-lg-row justify-content-between gap-2">
                <strong>Tienda online 2026</strong>
                <small>Experiencia de compra separada del panel administrativo.</small>
            </div>
        </footer>
    </div>

    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        crossorigin="anonymous"
    ></script>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
        crossorigin="anonymous"
    ></script>
    @stack('scripts')
</body>
</html>
