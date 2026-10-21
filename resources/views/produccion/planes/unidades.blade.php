@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Unidades de producción</h1>
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
                <h4 class="mb-0">
                    {{ $planProducto->unidadesProduccion->count() }} unidades
                </h4>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Unidad</th>
                                <th>Chasis</th>
                                <th>Serie</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Color</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($planProducto->unidadesProduccion as $unidad)
                                <tr>
                                    <td class="fw-semibold">
                                        Unidad {{ $unidad->numero_unidad }}
                                    </td>

                                    <td>
                                        {{ $unidad->chasis ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $unidad->serie ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $unidad->cliente?->nombre ?? '-' }}
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            {{ ucfirst($unidad->estado) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $unidad->color ?? '-' }}
                                    </td>

                                    <td>
                                        <a href="{{ route('planes.productos.unidades.edit', [$plan, $planProducto, $unidad]) }}"
                                            class="btn btn-sm btn-warning">
                                            Editar
                                        </a>

                                        <a href="{{ route('planes.productos.unidades.componentes.index', [$plan, $planProducto, $unidad]) }}"
                                            class="btn btn-sm btn-primary">
                                            Componentes
                                        </a>

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

                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
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
