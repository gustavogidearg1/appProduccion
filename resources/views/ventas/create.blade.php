@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Nueva venta</h1>
            <p class="text-muted mb-0">
                Cargar una nueva venta
            </p>
        </div>

        <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
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

    <form method="POST" action="{{ route('ventas.store') }}">
        @csrf

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
                            name="cliente_id"
                            id="cliente_id"
                            class="form-select"
                            required
                        >
                            <option value="">
                                Seleccionar cliente
                            </option>

                            @foreach ($clientes as $cliente)
                                <option
                                    value="{{ $cliente->id }}"
                                    @selected(
                                        old('cliente_id') == $cliente->id
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
                            name="fecha"
                            id="fecha"
                            class="form-control"
                            value="{{ old('fecha', now()->format('Y-m-d')) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="estado" class="form-label">
                            Estado
                        </label>

                        <select
                            name="estado"
                            id="estado"
                            class="form-select"
                            required
                        >
                            <option
                                value="borrador"
                                @selected(old('estado', 'borrador') === 'borrador')
                            >
                                Borrador
                            </option>

                            <option
                                value="confirmada"
                                @selected(old('estado') === 'confirmada')
                            >
                                Confirmada
                            </option>

                            <option
                                value="entregada"
                                @selected(old('estado') === 'entregada')
                            >
                                Entregada
                            </option>

                            <option
                                value="anulada"
                                @selected(old('estado') === 'anulada')
                            >
                                Anulada
                            </option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="observaciones" class="form-label">
                            Observaciones
                        </label>

                        <textarea
                            name="observaciones"
                            id="observaciones"
                            rows="4"
                            class="form-control"
                        >{{ old('observaciones') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Productos</span>

                <button
                    type="button"
                    id="agregar-producto"
                    class="btn btn-sm btn-primary"
                >
                    <i class="bi bi-plus-circle me-1"></i>
                    Agregar producto
                </button>
            </div>

            <div class="card-body">
                <div id="productos-container">

                    <div class="producto-row border rounded p-3 mb-3">
                        <div class="row g-3 align-items-end">

                            <div class="col-md-5">
                                <label class="form-label">
                                    Producto
                                </label>

                                <select
                                    name="detalles[0][producto_id]"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        Seleccionar producto
                                    </option>

                                    @foreach ($productos as $producto)
                                        <option
                                            value="{{ $producto->id }}"
                                            @selected(
                                                (string) old(
                                                    'detalles.0.producto_id',
                                                    $productoSeleccionadoId
                                                ) ===
                                                (string) $producto->id
                                            )
                                        >
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
                                    name="detalles[0][cantidad]"
                                    class="form-control"
                                    min="0.01"
                                    step="0.01"
                                    value="{{ old('detalles.0.cantidad', 1) }}"
                                    required
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    Precio unitario
                                </label>

                                <input
                                    type="number"
                                    name="detalles[0][precio_unitario]"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('detalles.0.precio_unitario') }}"
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
                    </div>

                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle me-1"></i>
                Guardar venta
            </button>

            <a
                href="{{ route('ventas.index') }}"
                class="btn btn-outline-secondary"
            >
                Cancelar
            </a>
        </div>
    </form>

</div>

<script>
    const productos = @json(
        $productos->map(function ($producto) {
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
            ];
        })->values()
    );

    let indiceProducto = 1;

    const contenedor = document.getElementById('productos-container');
    const botonAgregar = document.getElementById('agregar-producto');

    function opcionesProductos() {
        let opciones = `
            <option value="">
                Seleccionar producto
            </option>
        `;

        productos.forEach(function (producto) {
            opciones += `
                <option value="${producto.id}">
                    ${producto.nombre}
                </option>
            `;
        });

        return opciones;
    }

    botonAgregar.addEventListener('click', function () {
        const fila = document.createElement('div');

        fila.className = 'producto-row border rounded p-3 mb-3';

        fila.innerHTML = `
            <div class="row g-3 align-items-end">

                <div class="col-md-5">
                    <label class="form-label">
                        Producto
                    </label>

                    <select
                        name="detalles[${indiceProducto}][producto_id]"
                        class="form-select"
                        required
                    >
                        ${opcionesProductos()}
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">
                        Cantidad
                    </label>

                    <input
                        type="number"
                        name="detalles[${indiceProducto}][cantidad]"
                        class="form-control"
                        min="0.01"
                        step="0.01"
                        value="1"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Precio unitario
                    </label>

                    <input
                        type="number"
                        name="detalles[${indiceProducto}][precio_unitario]"
                        class="form-control"
                        min="0"
                        step="0.01"
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
        `;

        contenedor.appendChild(fila);

        indiceProducto++;
    });

    contenedor.addEventListener('click', function (event) {
        if (!event.target.classList.contains('quitar-producto')) {
            return;
        }

        const filas = contenedor.querySelectorAll('.producto-row');

        if (filas.length === 1) {
            return;
        }

        event.target.closest('.producto-row').remove();
    });
</script>
@endsection
