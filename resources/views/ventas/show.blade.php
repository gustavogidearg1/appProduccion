@extends('layouts.app')

@section('content')
    <style>
        @media print {
            @page { margin: 12mm; }
            .navbar, .menu-lateral, .offcanvas { display: none !important; }
            main.contenido-principal { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
            .table-responsive { overflow: visible !important; }
            .card { box-shadow: none !important; }
            tr { break-inside: avoid; }
            .badge { color: #000 !important; background: transparent !important; padding-left: 0; }
        }
    </style>
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Venta #{{ $venta->id }}</h1>
                <p class="text-muted mb-0">
                    Detalle de la venta
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 d-print-none">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir venta
                </button>
                @if ($venta->estado !== 'anulada')
                    <a
                        href="{{ route('ventas.edit', $venta) }}"
                        class="btn btn-warning"
                    >
                        <i class="bi bi-pencil"></i>
                        Editar
                    </a>

                    <form
                        method="POST"
                        action="{{ route('ventas.anular', $venta) }}"
                        onsubmit="return confirm('¿Seguro que desea anular esta venta?');"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            <i class="bi bi-x-circle"></i>
                            Anular venta
                        </button>
                    </form>
                @endif

                <a
                    href="{{ route('ventas.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Volver
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success d-print-none">
                {{ session('success') }}
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                Datos de la venta
            </div>

            <div class="card-body">
                <div class="row g-4">

                    <div class="col-md-3">
                        <strong>Cliente</strong><br>
                        {{ $venta->cliente?->nombre ?? '-' }}
                    </div>

                    <div class="col-md-3">
                        <strong>Fecha</strong><br>
                        {{ $venta->fecha?->format('d/m/Y') ?? '-' }}
                    </div>

                    <div class="col-md-3"><strong>Entrega pactada / solicitada</strong><br>{{ $venta->fecha_entrega_pactada?->format('d/m/Y') ?? 'Sin definir' }}</div>

                    <div class="col-md-3">
                        <strong>Estado</strong><br>

                        <span class="badge bg-primary">
                            {{ ucfirst($venta->estado) }}
                        </span>
                    </div>

                    <div class="col-12">
                        <strong>Observaciones</strong><br>
                        {{ $venta->observaciones ?: '-' }}
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Productos vendidos</span>

                @if ($venta->estado !== 'anulada')
                    <a
                        href="{{ route('ventas.edit', $venta) }}"
                        class="btn btn-primary btn-sm d-print-none"
                    >
                        <i class="bi bi-plus-circle"></i>
                        Agregar o editar productos
                    </a>
                @endif
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th class="d-print-none">Precio unitario</th>
                                <th>Subtotal</th>
                                <th>Unidades asignadas</th>
                                <th class="d-print-none">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($venta->detalles as $detalle)

                                @php
                                    $asignacionesActivas = (
                                        $detalle->asignaciones ?? collect()
                                    )->whereIn(
                                        'estado',
                                        ['reservada', 'asignada']
                                    );

                                    $cantidadAsignada =
                                        $asignacionesActivas->count();

                                    $cantidadVendida =
                                        (float) $detalle->cantidad;
                                @endphp

                                <tr>
                                    <td class="fw-semibold">
                                        {{ $detalle->producto?->nombre ?? 'Producto no disponible' }}
                                    </td>

                                    <td>
                                        {{ number_format(
                                            (float) $detalle->cantidad,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="d-print-none">
                                        $
                                        {{ number_format(
                                            (float) $detalle->precio_unitario,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td>
                                        $
                                        {{ number_format(
                                            (float) $detalle->subtotal,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td>
                                        @if ($asignacionesActivas->isNotEmpty())
                                            @foreach ($asignacionesActivas as $asignacion)
                                                <div class="mb-1">
                                                    <span class="badge bg-dark">
                                                        Chasis
                                                        {{ $asignacion->unidad?->chasis ?? '-' }}
                                                    </span>

                                                    <span class="badge bg-success d-print-none">
                                                        {{ ucfirst($asignacion->estado) }}
                                                    </span>
                                                    @if ($asignacion->unidad)
                                                        @php($estimacion = $asignacion->unidad->estimacion_entrega)
                                                        <div class="small">Etapa: {{ $estimacion['excluida'] ? $estimacion['texto'] : $estimacion['etapa'] }}</div>
                                                        <div class="small d-none d-print-block">
                                                            Entrega estimada: {{ $estimacion['fecha'] ? \Carbon\Carbon::parse($estimacion['fecha'])->format('d/m/Y') : '—' }}
                                                        </div>
                                                        <div class="small mt-2 d-print-none">
                                                            <strong>Entrega estimada actual</strong>
                                                            @include('planificacion.estimacion', ['estimacion'=>$asignacion->unidad->estimacion_entrega])
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">
                                                Sin asignar
                                            </span>
                                        @endif
                                    </td>

                                    <td class="d-print-none">
                                        @if (!$detalle->producto?->esImplemento())
                                            <span class="text-muted">—</span>
                                        @elseif (
                                            $venta->estado !== 'anulada' &&
                                            $cantidadAsignada < $cantidadVendida
                                        )
                                            <a
                                                href="{{ route(
                                                    'ventas.detalles.asignar',
                                                    $detalle
                                                ) }}"
                                                class="btn btn-sm btn-success"
                                            >
                                                <i class="bi bi-link-45deg"></i>
                                                Asignar unidad
                                            </a>
                                        @elseif ($cantidadAsignada >= $cantidadVendida)
                                            <span class="badge bg-success">
                                                Cantidad cubierta
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Venta anulada
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-muted py-4"
                                    >
                                        La venta no tiene productos cargados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end d-print-none">
                                    Total
                                </th>

                                <th colspan="3" class="text-end d-none d-print-table-cell">Total</th>
                                <th>
                                    $
                                    {{ number_format(
                                        (float) $venta->total,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                </th>
                                <td class="d-print-none"></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>

    </div>
@endsection
