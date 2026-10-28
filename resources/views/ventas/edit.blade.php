@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Editar venta #{{ $venta->id }}</h1>
                <p class="text-muted mb-0">
                    Modificar datos y productos de la venta
                </p>
            </div>

            <a
                href="{{ route('ventas.show', $venta) }}"
                class="btn btn-outline-secondary"
            >
                Volver
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('ventas.update', $venta) }}"
        >
            @csrf
            @method('PUT')

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    Datos de la venta
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label for="cliente_id" class="form-label">
                                Cliente
                            </label>

                            <select
                                id="cliente_id"
                                name="cliente_id"
                                class="form-select"
                                required
                            >
                                @foreach ($clientes as $cliente)
                                    <option
                                        value="{{ $cliente->id }}"
                                        @selected(
                                            old(
                                                'cliente_id',
                                                $venta->cliente_id
                                            ) == $cliente->id
                                        )
                                    >
                                        {{ $cliente->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="fecha" class="form-label">
                                Fecha
                            </label>

                            <input
                                type="date"
                                id="fecha"
                                name="fecha"
                                class="form-control"
                                value="{{ old(
                                    'fecha',
                                    $venta->fecha?->format('Y-m-d')
                                ) }}"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="estado" class="form-label">
                                Estado
                            </label>

                            <select
                                id="estado"
                                name="estado"
                                class="form-select"
                                required
                            >
                                @foreach ([
                                    'borrador' => 'Borrador',
                                    'confirmada' => 'Confirmada',
                                    'entregada' => 'Entregada',
                                    'anulada' => 'Anulada',
                                ] as $valor => $nombre)
                                    <option
                                        value="{{ $valor }}"
                                        @selected(
                                            old(
                                                'estado',
                                                $venta->estado
                                            ) === $valor
                                        )
                                    >
                                        {{ $nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label
                                for="observaciones"
                                class="form-label"
                            >
                                Observaciones
                            </label>

                            <textarea
                                id="observaciones"
                                name="observaciones"
                                rows="3"
                                class="form-control"
                            >{{ old(
                                'observaciones',
                                $venta->observaciones
                            ) }}</textarea>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    Productos actuales
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th>Chasis y entrega estimada</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($venta->detalles as $detalle)
                                    @php
                                        $asignadas = $detalle->asignaciones
                                            ->whereIn(
                                                'estado',
                                                ['reservada', 'asignada']
                                            )
                                            ->count();

                                        $tipo = $detalle->producto?->tipo?->nombre
                                            ?? 'Accesorio';
                                    @endphp

                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $tipo }}
                                            </span>
                                        </td>

                                        <td class="fw-semibold">
                                            {{ $detalle->producto?->nombre ?? '-' }}
                                        </td>

                                        <td style="max-width: 150px;">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="{{ $asignadas > 0 ? $asignadas : '0.01' }}"
                                                name="detalles[{{ $detalle->id }}][cantidad]"
                                                class="form-control"
                                                value="{{ old(
                                                    'detalles.' . $detalle->id . '.cantidad',
                                                    $detalle->cantidad
                                                ) }}"
                                                required
                                            >
                                        </td>

                                        <td style="max-width: 180px;">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="detalles[{{ $detalle->id }}][precio_unitario]"
                                                class="form-control"
                                                value="{{ old(
                                                    'detalles.' . $detalle->id . '.precio_unitario',
                                                    $detalle->precio_unitario
                                                ) }}"
                                                required
                                            >
                                        </td>

                                        <td>
                                            @if ($asignadas > 0)
                                                <div class="small text-muted mb-2">{{ $asignadas }} unidades reservadas / asignadas</div>
                                                @foreach ($detalle->asignaciones->whereIn('estado', ['reservada', 'asignada']) as $asignacion)
                                                    <div class="border rounded p-2 mb-2" style="min-width:230px">
                                                        <span class="badge bg-dark">Chasis {{ $asignacion->unidad?->chasis ?: 'Sin cargar' }}</span>
                                                        <span class="badge bg-success">{{ ucfirst($asignacion->estado) }}</span>
                                                        @if ($asignacion->unidad)
                                                            <div class="small mt-2"><strong>Entrega estimada actual</strong>
                                                                @include('planificacion.estimacion', ['estimacion'=>$asignacion->unidad->estimacion_entrega])
                                                            </div>
                                                        @else
                                                            <div class="small text-muted">Unidad no disponible</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-muted">
                                                    Sin asignar
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Agregar productos</span>

                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        id="agregar-producto"
                    >
                        <i class="bi bi-plus-circle"></i>
                        Agregar producto
                    </button>
                </div>

                <div class="card-body">
                    <div id="productos-nuevos"></div>

                    <p
                        id="sin-productos"
                        class="text-muted mb-0"
                    >
                        No hay productos nuevos para agregar.
                    </p>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Guardar cambios
                </button>

                <a
                    href="{{ route('ventas.show', $venta) }}"
                    class="btn btn-outline-secondary"
                >
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <template id="producto-template">
        <div class="row g-3 align-items-end mb-3 producto-nuevo">
            <div class="col-md-6">
                <label class="form-label">
                    Producto
                </label>

                <select
                    name="nuevos_productos[__INDEX__][producto_id]"
                    class="form-select"
                    required
                >
                    <option value="">
                        Seleccionar producto
                    </option>

                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}">
                            {{ $producto->tipo?->nombre ?? 'Accesorio' }}
                            -
                            {{ $producto->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">
                    Cantidad
                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="nuevos_productos[__INDEX__][cantidad]"
                    class="form-control"
                    required
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">
                    Precio unitario
                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="nuevos_productos[__INDEX__][precio_unitario]"
                    class="form-control"
                    required
                >
            </div>

            <div class="col-md-2">
                <button
                    type="button"
                    class="btn btn-outline-danger w-100 quitar-producto"
                >
                    Quitar
                </button>
            </div>
        </div>
    </template>

    <script>
        let indiceProducto = 0;

        const botonAgregar = document.getElementById('agregar-producto');
        const contenedor = document.getElementById('productos-nuevos');
        const mensajeVacio = document.getElementById('sin-productos');
        const template = document.getElementById('producto-template');

        botonAgregar.addEventListener('click', function () {
            const html = template.innerHTML.replaceAll(
                '__INDEX__',
                indiceProducto
            );

            contenedor.insertAdjacentHTML('beforeend', html);
            indiceProducto++;

            mensajeVacio.classList.add('d-none');
        });

        contenedor.addEventListener('click', function (event) {
            if (!event.target.classList.contains('quitar-producto')) {
                return;
            }

            event.target
                .closest('.producto-nuevo')
                .remove();

            if (!contenedor.children.length) {
                mensajeVacio.classList.remove('d-none');
            }
        });
    </script>
@endsection
