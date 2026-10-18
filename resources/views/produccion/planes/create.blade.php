@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <h1 class="mb-4">Nuevo plan de producción</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="{{ route('planes.store') }}">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Número de plan</label>
                        <input
                            type="number"
                            class="form-control"
                            name="numero"
                            value="{{ old('numero') }}"
                            min="1"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha planificada</label>
                        <input
                            type="date"
                            class="form-control"
                            name="fecha_planificada"
                            value="{{ old('fecha_planificada') }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Prioridad</label>
                        <input
                            type="number"
                            class="form-control"
                            name="prioridad"
                            value="{{ old('prioridad', 0) }}"
                            min="0"
                            max="255"
                        >
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Productos del plan</h4>

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="agregar-producto"
                    >
                        + Agregar producto
                    </button>
                </div>

                <div id="productos-container">

                    @php
                        $productosSeleccionados = old('producto_id', ['']);
                    @endphp

                    @foreach($productosSeleccionados as $indice => $productoSeleccionado)
                        <div class="producto-row border rounded p-3 mb-3">

                            <div class="row g-3 align-items-end">

                                <div class="col-md-6">
                                    <label class="form-label">Producto</label>

                                    <select
                                        name="producto_id[]"
                                        class="form-select"
                                        required
                                    >
                                        <option value="">Seleccioná un producto</option>

                                        @foreach($productos as $producto)
                                            <option
                                                value="{{ $producto->id }}"
                                                @selected((string) $productoSeleccionado === (string) $producto->id)
                                            >
                                                {{ $producto->codigo }} -
                                                {{ $producto->nombre }}

                                                @if(!is_null($producto->ue_produccion))
                                                    | UE: {{ $producto->ue_produccion }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Cantidad</label>

                                    <input
                                        type="number"
                                        name="cantidad[]"
                                        class="form-control"
                                        value="{{ old('cantidad.' . $indice, 1) }}"
                                        min="0.01"
                                        step="0.01"
                                        required
                                    >
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Observación</label>

                                    <input
                                        type="text"
                                        name="observaciones[]"
                                        class="form-control"
                                        value="{{ old('observaciones.' . $indice) }}"
                                        placeholder="Observación del producto"
                                    >
                                </div>

                                <div class="col-md-1">
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger eliminar-producto w-100"
                                        title="Eliminar producto"
                                    >
                                        ×
                                    </button>
                                </div>

                            </div>
                        </div>
                    @endforeach

                </div>

                <div class="mb-4">
                    <label class="form-label">Observaciones generales del plan</label>

                    <textarea
                        name="observaciones_plan"
                        class="form-control"
                        rows="4"
                        placeholder="Observaciones generales"
                    >{{ old('observaciones_plan') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    Guardar plan
                </button>

                <a
                    href="{{ route('planes.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Cancelar
                </a>

            </form>

        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('productos-container');
    const botonAgregar = document.getElementById('agregar-producto');

    function crearFilaProducto() {
        const fila = document.querySelector('.producto-row').cloneNode(true);

        fila.querySelector('select').selectedIndex = 0;
        fila.querySelector('input[name="cantidad[]"]').value = 1;
        fila.querySelector('input[name="observaciones[]"]').value = '';

        container.appendChild(fila);
    }

    botonAgregar.addEventListener('click', function () {
        crearFilaProducto();
    });

    container.addEventListener('click', function (event) {
        if (!event.target.classList.contains('eliminar-producto')) {
            return;
        }

        const filas = container.querySelectorAll('.producto-row');

        if (filas.length > 1) {
            event.target.closest('.producto-row').remove();
        } else {
            filas[0].querySelector('select').selectedIndex = 0;
            filas[0].querySelector('input[name="cantidad[]"]').value = 1;
            filas[0].querySelector('input[name="observaciones[]"]').value = '';
        }
    });
});
</script>
@endsection
