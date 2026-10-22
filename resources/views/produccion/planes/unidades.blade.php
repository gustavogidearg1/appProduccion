@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-start
                gap-3 mb-4">

            <div>
                <h1 class="mb-1">Unidades de producción</h1>

                <p class="text-muted mb-0">
                    Plan #{{ $plan->numero }} -
                    {{ $planProducto->producto?->nombre ?? 'Producto no disponible' }}
                </p>
            </div>

            <a href="{{ route('planes.show', $plan) }}" class="btn btn-outline-secondary">
                Volver al plan
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="h4 mb-0">
                    {{ $planProducto->unidadesProduccion->count() }} unidades
                </h2>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Chasis</th>
                                <th>Serie</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Color</th>
                                <th>Observaciones</th>
                                <th>Componentes</th>
                                <th>Calidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($planProducto->unidadesProduccion as $unidad)
                                @php
                                    $cantidadComponentes = $unidad->componentes?->count() ?? 0;

                                    $controles = $unidad->controlesCalidad ?? collect();

                                    $aprobados = $controles->whereIn('resultado', ['aprobado', 'no_aplica'])->count();

                                    $pendientes = $controles->where('resultado', 'pendiente')->count();

                                    $observados = $controles->whereIn('resultado', ['observado', 'rechazado'])->count();

                                    $estado = str_replace('_', ' ', $unidad->estado);
                                @endphp

                                <tr>
                                    <td class="fw-semibold">
                                        {{ $unidad->chasis ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $unidad->serie ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $unidad->cliente?->nombre ?? '-' }}
                                    </td>

                                    <td>
                                        @if ($unidad->estado === 'entregada')
                                            <span class="badge bg-success">
                                                Entregada
                                            </span>
                                        @elseif ($unidad->estado === 'terminada')
                                            <span class="badge bg-primary">
                                                Terminada
                                            </span>
                                        @elseif ($unidad->estado === 'en_produccion')
                                            <span class="badge bg-warning text-dark">
                                                En producción
                                            </span>
                                        @elseif ($unidad->estado === 'cancelada')
                                            <span class="badge bg-danger">
                                                Cancelada
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                {{ ucfirst($estado) }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $unidad->color ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $unidad->observaciones ?: '-' }}
                                    </td>

                                    <td>
                                        <a href="{{ route('planes.productos.unidades.componentes.index', [$plan, $planProducto, $unidad]) }}"
                                            class="btn btn-sm btn-primary">
                                            Componentes
                                            <span class="badge bg-light text-dark">
                                                {{ $cantidadComponentes }}
                                            </span>
                                        </a>
                                    </td>

                                    <td>
                                        @if ($controles->count() === 0)
                                            <span class="badge bg-secondary">
                                                Sin controles
                                            </span>
                                        @elseif ($pendientes > 0)
                                            <span class="badge bg-warning text-dark">
                                                Pendientes: {{ $pendientes }}
                                            </span>
                                        @elseif ($observados > 0)
                                            <span class="badge bg-danger">
                                                Observados: {{ $observados }}
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                OK: {{ $aprobados }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex flex-wrap gap-1">

                                            <a href="{{ route('planes.productos.unidades.show', [$plan, $planProducto, $unidad]) }}"
                                                class="btn btn-sm btn-outline-dark">
                                                <i class="bi bi-file-earmark-text me-1"></i>
                                                Ficha
                                            </a>

                                            @if ($unidad->estado !== 'entregada')
                                                <a href="{{ route('planes.productos.unidades.edit', [$plan, $planProducto, $unidad]) }}"
                                                    class="btn btn-sm btn-warning">
                                                    Editar
                                                </a>
                                            @endif

                                            <a href="{{ route('planes.productos.unidades.avances', [$plan, $planProducto, $unidad]) }}"
                                                class="btn btn-sm btn-success">
                                                Avances
                                            </a>

                                            <a href="{{ route('planes.productos.unidades.calidad', [$plan, $planProducto, $unidad]) }}"
                                                class="btn btn-sm btn-success">
                                                Calidad
                                            </a>

                                            <a href="{{ route('planes.productos.unidades.qr', [$plan, $planProducto, $unidad]) }}"
                                                target="_blank" class="btn btn-sm btn-dark">
                                                <i class="bi bi-qr-code me-1"></i>
                                                QR
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        No hay unidades generadas.
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
