@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Venta #{{ $venta->id }}</h1>
                <p class="text-muted mb-0">
                    Detalle de la venta
                </p>
            </div>

            <div class="d-flex gap-2">
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
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                Datos de la venta
            </div>

            <div class="card-body">
                <div class="row g-4">

                    <div class="col-md-4">
                        <strong>Cliente</strong><br>
                        {{ $venta->cliente?->nombre ?? '-' }}
                    </div>

                    <div class="col-md-4">
                        <strong>Fecha</strong><br>
                        {{ $venta->fecha?->format('d/m/Y') ?? '-' }}
                    </div>

                    <div class="col-md-4">
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
                        class="btn btn-primary btn-sm"
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
                                <th>Precio unitario</th>
                                <th>Subtotal</th>
                                <th>Unidades asignadas</th>
                                <th>Acciones</th>
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

                                    <td>
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

                                                    <span class="badge bg-success">
                                                        {{ ucfirst($asignacion->estado) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">
                                                Sin asignar
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (
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
                                <th colspan="4" class="text-end">
                                    Total
                                </th>

                                <th colspan="2">
                                    $
                                    {{ number_format(
                                        (float) $venta->total,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>

    </div>
@endsection
