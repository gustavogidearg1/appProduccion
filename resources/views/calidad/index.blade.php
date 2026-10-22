@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h1>Calidad</h1>

        <p class="text-muted mb-0">
            Seleccioná una familia para configurar sus controles.
        </p>
    </div>

    <div class="row g-4">
        @forelse ($familias as $familia)
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">
                            {{ $familia->nombre }}
                        </h2>

                        <p class="text-muted">
                            Configuración de controles de calidad.
                        </p>

                        <a href="{{ route(
                            'calidad.familias.controles.index',
                            $familia
                        ) }}"
                           class="btn btn-primary">
                            Ver controles
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    No hay familias disponibles.
                </div>
            </div>
        @endforelse
    </div>

</div>
@endsection
