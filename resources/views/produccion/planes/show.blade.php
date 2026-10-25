@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-column flex-md-row
                justify-content-between align-items-start
                gap-3 mb-4">

        <div>
            <h1 class="mb-1">
                Plan de producción #{{ $plan->numero }}
            </h1>

            <p class="text-muted mb-0">
                Detalle del plan
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('planes.index') }}"
               class="btn btn-outline-secondary">
                Volver
            </a>

            <a href="{{ route('planes.avances', $plan) }}"
               class="btn btn-success">
                Avances del plan
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">

                <div class="col-12 col-md-3">
                    <strong>Estado</strong><br>

                    <span class="badge bg-primary">
                        {{ ucfirst(str_replace('_', ' ', $plan->estado)) }}
                    </span>
                </div>

                <div class="col-12 col-md-3">
                    <strong>Fecha planificada</strong><br>

                    {{ $plan->fecha_planificada?->format('d/m/Y') ?? '-' }}
                </div>

                <div class="col-12 col-md-3">
                    <strong>Fecha de finalización planificada</strong><br>

                    {{ $plan->fecha_fin_planificada?->format('d/m/Y') ?? '-' }}
                </div>

                <div class="col-12">
                    <strong>Observaciones generales</strong><br>

                    {{ $plan->observaciones ?: '-' }}
                </div>

            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h2 class="h4 mb-0">
                Productos del plan
            </h2>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Chasis</th>
                            <th>UE producción</th>
                            <th>Cantidad</th>
                            <th>Observación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($plan->planProductos as $linea)
                            <tr>
                                <td>
                                    {{ $linea->producto?->codigo ?? '-' }}
                                </td>

                                <td class="fw-semibold">
                                    {{ $linea->producto?->nombre ?? 'Producto no disponible' }}
                                </td>

                                <td>
    @foreach($linea->unidadesProduccion as $unidad)
        @php
            $avanceActual = $unidad->avances
                ->where('estado', 'en_proceso')
                ->sortBy(fn ($avance) =>
                    $avance->etapa?->orden ?? 999
                )
                ->first();

            $ultimaEtapa = $unidad->avances
                ->where('estado', 'completada')
                ->sortByDesc(fn ($avance) =>
                    $avance->etapa?->orden ?? 0
                )
                ->first();

            $estadoActual =
                $avanceActual?->etapa?->nombre
                ?? $ultimaEtapa?->etapa?->nombre
                ?? 'Planificada';
        @endphp

        <div class="mb-1">
            <span class="badge bg-dark">
                {{ $unidad->chasis }}
            </span>

            <span class="badge bg-secondary">
                {{ $estadoActual }}
            </span>
        </div>
    @endforeach
</td>

                                <td>
                                    {{ $linea->producto?->ue_produccion ?? '-' }}
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $linea->cantidad,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td>
                                    {{ $linea->observaciones ?: '-' }}
                                </td>

                                <td>
                                    @if ($linea->unidadesProduccion->isEmpty())
                                        <form method="POST"
                                              action="{{ route(
                                                  'planes.productos.generar-unidades',
                                                  [$plan, $linea]
                                              ) }}">
                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-sm btn-primary">
                                                Generar unidades
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route(
                                            'planes.productos.unidades',
                                            [$plan, $linea]
                                        ) }}"
                                           class="btn btn-sm btn-success">
                                            Ver unidades
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="text-center py-4 text-muted">
                                    El plan no tiene productos cargados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

</div>
@endsection
