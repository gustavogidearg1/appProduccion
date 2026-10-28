@extends('layouts.app')

@section('content')

<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body {
            background: #fff !important;
            font-size: 12px;
        }

        .navbar,
        aside,
        .no-print {
            display: none !important;
        }

        main {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            padding: 0 !important;
        }

        .container-fluid {
            width: 100% !important;
            padding: 0 !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #444 !important;
            break-inside: avoid;
        }

        .row {
            display: flex !important;
        }

        .col-lg-8 {
            width: 68% !important;
        }

        .col-lg-4 {
            width: 32% !important;
        }

        .badge {
            border: 1px solid #333;
            color: #000 !important;
            background: #fff !important;
        }

        .print-qr img {
            max-width: 150px !important;
        }
    }

    @media print {
    body * {
        visibility: hidden !important;
    }

    #ficha-chasis,
    #ficha-chasis * {
        visibility: visible !important;
    }

    #ficha-chasis {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    #ficha-chasis .no-print {
        display: none !important;
    }
}
</style>
<div id="ficha-chasis" class="container-fluid py-4">



    <div class="d-flex flex-column flex-md-row
            justify-content-between align-items-start
            gap-3 mb-4">

    <div>
        <h1 class="mb-1">
            Chasis {{ $unidad->chasis ?? 'sin asignar' }}
        </h1>

        <p class="text-muted mb-0">
            Plan #{{ $plan->numero }} -
            {{ $planProducto->producto?->nombre ?? '-' }}
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2 no-print">

        <a href="{{ route(
            'planes.productos.unidades',
            [$plan, $planProducto]
        ) }}"
           class="btn btn-outline-secondary">
            Volver a unidades
        </a>

        <button type="button"
                onclick="window.print()"
                class="btn btn-primary">
            <i class="bi bi-printer me-1"></i>
            Imprimir ficha
        </button>

    </div>

</div>

    <div class="row g-4">

        <div class="col-12 col-lg-8">

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h4 mb-0">
                        Información de la unidad
                    </h2>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-12 col-md-6">
                            <div class="text-muted small">
                                Plan
                            </div>

                            <div class="fs-5 fw-semibold">
                                #{{ $plan->numero }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="text-muted small">
                                Producto
                            </div>

                            <div class="fw-semibold">
                                {{ $planProducto->producto?->nombre ?? '-' }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="text-muted small">
                                Chasis
                            </div>

                            <div class="fs-5 fw-semibold">
                                {{ $unidad->chasis ?? '-' }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="text-muted small">
                                Serie
                            </div>

                            <div class="fs-5">
                                {{ $unidad->serie ?? '-' }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="text-muted small">
                                Cliente
                            </div>

                            <div class="fs-5">
                                {{ $ventaVinculada ? ($ventaVinculada->cliente?->nombre ?? 'Cliente no disponible') : ($unidad->cliente?->nombre ?? '-') }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="text-muted small">
                                Estado
                            </div>

                            @if ($unidad->estado === 'entregada')
                                <span class="badge bg-success fs-6">
                                    Entregada
                                </span>
                            @elseif ($unidad->estado === 'terminada')
                                <span class="badge bg-primary fs-6">
                                    Terminada
                                </span>
                            @elseif ($unidad->estado === 'en_produccion')
                                <span class="badge bg-warning text-dark fs-6">
                                    En producción
                                </span>
                            @else
                                <span class="badge bg-secondary fs-6">
                                    {{ ucfirst(str_replace('_', ' ', $unidad->estado)) }}
                                </span>
                            @endif
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="text-muted small">
                                Color
                            </div>

                            <div class="fs-5">
                                {{ $unidad->color ?? '-' }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="text-muted small">
                                Observaciones
                            </div>

                            <div>
                                {{ $unidad->observaciones ?: '-' }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0">Información de venta</h2>
                    @if ($ventaVinculada)
                        <a href="{{ route('ventas.show', $ventaVinculada) }}" class="btn btn-sm btn-outline-primary no-print">Ver venta #{{ $ventaVinculada->id }}</a>
                    @endif
                </div>
                @if ($ventaVinculada)
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><span class="text-muted small">Venta</span><div class="fw-semibold">#{{ $ventaVinculada->id }} · {{ $ventaVinculada->fecha?->format('d/m/Y') ?? '-' }}</div></div>
                            <div class="col-md-6"><span class="text-muted small">Cliente de la venta</span><div class="fw-semibold">{{ $ventaVinculada->cliente?->nombre ?? 'Cliente no disponible' }}</div></div>
                            <div class="col-md-6"><span class="text-muted small">Estado de venta</span><div>{{ ucfirst($ventaVinculada->estado) }}</div></div>
                            <div class="col-md-6"><span class="text-muted small">Asignación del chasis</span><div><span class="badge bg-success">{{ ucfirst($asignacionVenta->estado) }}</span></div></div>
                            <div class="col-12"><span class="text-muted small">Producto vendido vinculado a este chasis</span><div class="fw-semibold">{{ $asignacionVenta->detalle->producto?->nombre ?? 'Producto no disponible' }}</div><div class="small text-muted">1 unidad de {{ number_format((float) $asignacionVenta->detalle->cantidad, 2, ',', '.') }} en el renglón de venta.</div></div>
                            <div class="col-12"><span class="text-muted small">Observaciones de venta</span><div style="white-space: pre-line">{{ $ventaVinculada->observaciones ?: '-' }}</div></div>
                            @if ($asignacionVenta->observaciones)
                                <div class="col-12"><span class="text-muted small">Observaciones de asignación</span><div style="white-space: pre-line">{{ $asignacionVenta->observaciones }}</div></div>
                            @endif
                        </div>
                    </div>
                    <div class="px-3 pb-2">
                        <h3 class="h6">Otros productos, componentes y accesorios de la venta</h3>
                        <p class="small text-muted mb-2">Cantidades del pedido completo. La pertenencia de estos adicionales a este chasis no está especificada.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th>Producto / componente</th><th>Tipo</th><th>Cantidad en venta</th></tr></thead>
                            <tbody>
                                @forelse ($otrosDetallesVenta as $detalleVenta)
                                    <tr><td>{{ $detalleVenta->producto?->nombre ?? 'Producto no disponible' }}</td><td>{{ $detalleVenta->producto?->tipo?->nombre ?? '-' }}</td><td>{{ number_format((float) $detalleVenta->cantidad, 2, ',', '.') }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted p-3">La venta no tiene otros productos, componentes o accesorios cargados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body text-muted">Esta unidad no tiene una reserva o asignación de venta activa.</div>
                @endif
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h4 mb-0">
                        Componentes y accesorios
                    </h2>
                </div>

                <div class="card-body p-0">
                    @if ($unidad->componentes->isEmpty())
                        <div class="p-3 text-muted">
                            No hay componentes registrados.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Componente</th>
                                        <th>Estado</th>
                                        <th>Uso</th>
                                        <th>Trazabilidad</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($unidad->componentes as $componente)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $componente->producto?->nombre ?? '-' }}
                                            </td>

                                            <td>
                                                {{ ucfirst($componente->estado) }}
                                            </td>

                                            <td>
                                                {{ ucfirst($componente->uso) }}
                                            </td>

                                            <td>
                                                {{ $componente->trazabilidad ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <div class="col-12 col-lg-4">

            <div class="card shadow-sm mb-4 text-center print-qr">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Identificación QR
                    </h2>
                </div>

                <div class="card-body">
                    <img src="{{ route(
                        'planes.productos.unidades.qr',
                        [$plan, $planProducto, $unidad]
                    ) }}"
                         alt="QR del chasis {{ $unidad->chasis }}"
                         class="img-fluid"
                         style="max-width: 280px;">

                    <p class="small text-muted mt-3 mb-0">
                        Escaneá este código desde un celular para abrir
                        la producción móvil.
                    </p>
                    <a href="{{ route('produccion.movil', ['unidad'=>$unidad->id]) }}" class="btn btn-sm btn-outline-primary mt-2 no-print">Abrir producción móvil</a>

                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        Resumen
                    </h2>
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span>Componentes</span>
                        <span class="badge bg-primary">
                            {{ $unidad->componentes->count() }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Controles de calidad</span>
                        <span class="badge bg-success">
                            {{ $unidad->controlesCalidad->count() }}
                        </span>
                    </div>

                </div>
            </div>

            <div class="card mb-3"><div class="card-body"><strong>Entrega estimada</strong>
                @include('planificacion.estimacion', ['estimacion'=>$unidad->estimacion_entrega])
            </div></div>
            <div class="d-grid gap-2">
                <a href="{{ route('entregas.index',$unidad) }}" class="btn btn-success no-print">Control de entrega y fotos</a>
                <a href="{{ route('demoras.index',[$plan,'unidad_id'=>$unidad->id]) }}" class="btn btn-outline-warning no-print">Demoras del chasis</a>


                <a href="{{ route(
                    'planes.productos.unidades.avances',
                    [$plan, $planProducto, $unidad]
                ) }}"
                   class="btn btn-success btn-lg no-print">
                    Avances
                </a>

                <a href="{{ route(
                    'planes.productos.unidades.calidad',
                    [$plan, $planProducto, $unidad]
                ) }}"
                   class="btn btn-primary btn-lg no-print">
                    Control de calidad
                </a>

                <a href="{{ route(
                    'planes.productos.unidades.componentes.index',
                    [$plan, $planProducto, $unidad]
                ) }}"
                   class="btn btn-outline-primary btn-lg no-print">
                    Componentes
                </a>

                @if ($unidad->estado !== 'entregada')
                    <a href="{{ route(
                        'planes.productos.unidades.edit',
                        [$plan, $planProducto, $unidad]
                    ) }}"
                       class="btn btn-outline-secondary btn-lg no-print">
                        Editar unidad
                    </a>
                @endif

            </div>

        </div>

    </div>

</div>
@endsection
