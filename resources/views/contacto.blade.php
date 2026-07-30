@extends('app')

@section('title', 'Sistema - Contacto')
@section('page-title', 'Contacto')

@section('contenido')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Contacto</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-lg-6">
                    <p class="text-secondary">Canales de atencion para consultas sobre productos y pedidos.</p>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-envelope me-2 text-primary"></i>ventas@sistema.local</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2 text-primary"></i>+595 981 000 000</li>
                        <li><i class="bi bi-clock me-2 text-primary"></i>Lunes a viernes, 08:00 a 18:00</li>
                    </ul>
                </div>
                <div class="col-12 col-lg-6 mt-4 mt-lg-0">
                    <a href="{{ route('catalog.index') }}" class="btn btn-primary">
                        <i class="bi bi-shop me-1"></i>
                        Ir al catalogo
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
