@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Asignar unidad a la venta</h1>

            <p class="text-muted mb-0">
                Venta #{{ $ventaDetalle->venta->id }}
                -
                {{ $ventaDetalle->venta->cliente?->nombre ?? '-' }}
            </p>

            <p class="text-muted mb-0">
                Producto:
                <strong>
                    {{ $ventaDetalle->producto?->nombre ?? '-' }}
                </strong>
            </p>
        </div>

        <a
            href="{{ route('ventas.show', $ventaDetalle->venta) }}"
            class="btn btn-outline-secondary"
        >
            Volver a la venta
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">Unidades disponibles</h4>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Chasis</th>
                            <th>Estado</th>
                            <th>Disponibilidad</th>
                            <th>Entrega estimada</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($unidades as $unidad)
                            @php
                                $estimacion = $unidad->estimacion_entrega;
                                $estadoActual = $estimacion['etapa'];
                                $esStock = $estimacion['inmediata'];
                            @endphp

                            <tr>
                                <td class="fw-semibold">
                                    {{ $unidad->chasis ?? '-' }}
                                </td>

                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $estadoActual }}
                                    </span>
                                </td>

                                <td>
                                    @if ($esStock)
                                        <span class="badge bg-success">
                                            Stock disponible
                                        </span>
                                    @else
                                        <span class="badge bg-primary">
                                            Producción disponible
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @include('planificacion.estimacion', ['estimacion'=>$estimacion])
                                </td>

                                <td>
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'ventas.detalles.asignar.store',
                                            $ventaDetalle
                                        ) }}"
                                    >
                                        @csrf

                                        <input
                                            type="hidden"
                                            name="unidad_produccion_id"
                                            value="{{ $unidad->id }}"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-success btn-sm"
                                        >
                                            Asignar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >
                                    No hay unidades disponibles para este
                                    producto.
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
