@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-start
                gap-3 mb-4">

            <div>
                <h1 class="mb-1">Plan de producción #{{ $plan->numero }}</h1>

                <p class="text-muted mb-0">
                    Plan #{{ $plan->numero }} -
                    {{ $planProducto->producto?->nombre ?? 'Producto no disponible' }}
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('planes.index') }}" class="btn btn-outline-secondary">
                    Volver a planes
                </a>
                <a href="{{ route('demoras.index', $plan) }}" class="btn btn-outline-warning">Demoras del plan</a>
                <a href="{{ route('planes.avances', $plan) }}" class="btn btn-success">
                    Avances del plan
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Estado del plan</strong><div><span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $plan->estado)) }}</span></div></div>
                    <div class="col-md-4"><strong>Fecha planificada</strong><div>{{ $plan->fecha_planificada?->format('d/m/Y') ?? '-' }}</div></div>
                    <div class="col-md-4"><strong>Finalización planificada</strong><div>{{ $plan->fecha_fin_planificada?->format('d/m/Y') ?? '-' }}</div></div>
                    <div class="col-12"><strong>Observaciones generales</strong><div style="white-space: pre-line">{{ $plan->observaciones ?: '-' }}</div></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h2 class="h5 mb-0">Productos del plan</h2></div>
            <div class="card-body">
                @if ($plan->planProductos->count() > 1)
                    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Productos del plan">
                        @foreach ($plan->planProductos as $linea)
                            <a href="{{ route('planes.productos.unidades', [$plan, $linea]) }}"
                               class="btn btn-sm {{ $linea->id === $planProducto->id ? 'btn-primary' : 'btn-outline-primary' }}"
                               @if ($linea->id === $planProducto->id) aria-current="page" @endif>
                                {{ $linea->producto?->nombre ?? 'Producto no disponible' }}
                                ({{ $linea->unidades_produccion_count }} unidades)
                            </a>
                        @endforeach
                    </nav>
                @endif
                <div class="row g-3">
                    <div class="col-md-6"><strong>Producto seleccionado</strong><div>{{ $planProducto->producto?->nombre ?? 'Producto no disponible' }}</div></div>
                    <div class="col-md-2"><strong>Código</strong><div>{{ $planProducto->producto?->codigo ?? '-' }}</div></div>
                    <div class="col-md-2"><strong>UE producción</strong><div>{{ $planProducto->producto?->ue_produccion ?? '-' }}</div></div>
                    <div class="col-md-2"><strong>Cantidad planificada</strong><div>{{ number_format((float) $planProducto->cantidad, 2, ',', '.') }}</div></div>
                    <div class="col-12"><strong>Observaciones del producto</strong><div style="white-space: pre-line">{{ $planProducto->observaciones ?: '-' }}</div></div>
                </div>
                @if ($planProducto->unidadesProduccion->isEmpty())
                    <form method="POST" action="{{ route('planes.productos.generar-unidades', [$plan, $planProducto]) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-primary">Generar unidades</button>
                    </form>
                @endif
            </div>
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
                                <th>Entrega estimada</th>
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
                                    $asignacionVenta = $unidad->asignaciones->first();
                                    $ventaVinculada = $asignacionVenta?->detalle?->venta;
                                    $cantidadComponentes = $unidad->componentes?->count() ?? 0;

                                    $avanceActual = $unidad->avances
                                        ->where('estado', 'en_proceso')
                                        ->sortBy(fn($avance) => $avance->etapa?->orden ?? 999)
                                        ->first();

                                    $controles = ($unidad->controlesCalidad ?? collect())->whereIn(
                                        'control_calidad_id',
                                        $controlesFamiliaIds,
                                    );

                                    $aprobados = $controles->whereIn('resultado', ['aprobado', 'no_aplica'])->count();

                                    $pendientes = $controles->where('resultado', 'pendiente')->count();

                                    $observados = $controles->whereIn('resultado', ['observado', 'rechazado'])->count();

                                    $estado = str_replace('_', ' ', $unidad->estado);

                                    $nombreEstado = $unidad->estimacion_entrega['excluida'] ? $unidad->estimacion_entrega['texto'] : $unidad->estimacion_entrega['etapa'];
                                @endphp

                                <tr>
                                    <td class="fw-semibold">
                                        {{ $unidad->chasis ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $unidad->serie ?: 'Sin cargar' }}
                                    </td>

                                    <td>
                                        {{ $ventaVinculada ? ($ventaVinculada->cliente?->nombre ?? 'Cliente no disponible') : ($unidad->cliente?->nombre ?? '-') }}
                                        @if ($ventaVinculada)
                                            <div class="small mt-1">
                                                <a href="{{ route('ventas.show', $ventaVinculada) }}">Venta #{{ $ventaVinculada->id }}</a>
                                                <span class="badge bg-success">{{ ucfirst($asignacionVenta->estado) }}</span>
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($avanceActual?->etapa)
                                            <span class="badge bg-warning text-dark">
                                                {{ $nombreEstado }}
                                            </span>
                                        @elseif ($unidad->estado === 'entregada')
                                            <span class="badge bg-success">
                                                Entregada
                                            </span>
                                        @elseif ($unidad->estado === 'terminada')
                                            <span class="badge bg-primary">
                                                Terminada
                                            </span>
                                        @elseif ($unidad->estado === 'cancelada')
                                            <span class="badge bg-danger">
                                                Cancelada
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                {{ ucfirst($nombreEstado) }}
                                            </span>
                                            @if (!$unidad->estimacion_entrega['excluida'] && $unidad->estimacion_entrega['origen'] === 'plan')<div class="small text-muted">Etapa del plan</div>@endif
                                        @endif
                                    </td>

                                    <td>
                                        @include('planificacion.estimacion', ['estimacion'=>$unidad->estimacion_entrega])
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

                                            <a href="{{ route('demoras.index', [$plan, 'unidad_id'=>$unidad->id]) }}" class="btn btn-sm btn-outline-warning">Demoras</a>
                                            <a href="{{ route('entregas.index', $unidad) }}" class="btn btn-sm btn-outline-success">Entrega</a>
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



                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">
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
