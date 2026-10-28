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

            <form id="crear-plan-form" method="POST" action="{{ route('planes.store') }}">
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
        <label class="form-label">Inicio planificado</label>
        <input
            type="date"
            class="form-control"
            name="fecha_planificada"
            value="{{ old('fecha_planificada') }}"
        >
    </div>

    <div class="col-md-4">
        <label class="form-label">Fin estimado del plan</label>
        <input
            type="date"
            class="form-control"
            name="fecha_fin_planificada"
            value="{{ old('fecha_fin_planificada') }}"
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

                                    @php
                                        $productoFila = $productos->firstWhere('id', $productoSeleccionado);
                                    @endphp
                                    <input type="hidden" name="producto_id[]" class="producto-id" value="{{ $productoSeleccionado }}">
                                    <div class="d-flex flex-wrap gap-2">
                                        <div class="form-control bg-light flex-grow-1 producto-resumen" style="width:auto;min-width:160px" aria-live="polite">
                                            @if ($productoFila){{ $productoFila->codigo }} - {{ $productoFila->nombre }}@if ($productoFila->ue_produccion !== null) | UE: {{ $productoFila->ue_produccion }}@endif
                                            @else Seleccioná un producto @endif
                                        </div>
                                        <button type="button" class="btn btn-outline-primary buscar-producto"><i class="bi bi-search me-1"></i>Buscar producto</button>
                                    </div>
                                    <div class="small text-danger error-producto" role="alert"></div>
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

@include('components.buscador-productos', ['productos'=>$productos])
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('productos-container');
    function limpiarFila(fila) {
        fila.querySelector('.producto-id').value = '';
        fila.querySelector('.producto-resumen').textContent = 'Seleccioná un producto';
        fila.querySelector('.error-producto').textContent = '';
        fila.querySelector('input[name="cantidad[]"]').value = 1;
        fila.querySelector('input[name="observaciones[]"]').value = '';
    }
    function buscar(fila) {
        const ids = [...container.querySelectorAll('.producto-row')].filter(otra => otra !== fila).map(otra => otra.querySelector('.producto-id').value).filter(Boolean);
        window.buscadorProductos.abrir(fila, ids);
    }
    document.getElementById('agregar-producto').addEventListener('click', () => {
        const fila = container.querySelector('.producto-row').cloneNode(true);
        limpiarFila(fila);
        container.appendChild(fila);
        buscar(fila);
    });
    container.addEventListener('click', evento => {
        const boton = evento.target.closest('button');
        if (!boton) return;
        const fila = boton.closest('.producto-row');
        if (boton.classList.contains('buscar-producto')) buscar(fila);
        if (boton.classList.contains('eliminar-producto')) {
            if (container.querySelectorAll('.producto-row').length > 1) fila.remove();
            else limpiarFila(fila);
        }
    });
    container.addEventListener('producto-seleccionado', evento => {
        const fila = evento.target.closest('.producto-row');
        const producto = evento.detail;
        fila.querySelector('.producto-id').value = producto.id;
        fila.querySelector('.producto-resumen').textContent = `${producto.codigo ? producto.codigo + ' - ' : ''}${producto.nombre}${producto.ue !== '' ? ' | UE: ' + producto.ue : ''}`;
        fila.querySelector('.error-producto').textContent = '';
    });
    document.getElementById('crear-plan-form').addEventListener('submit', evento => {
        const fila = [...container.querySelectorAll('.producto-row')].find(f => !f.querySelector('.producto-id').value);
        if (fila) {
            evento.preventDefault();
            fila.querySelector('.error-producto').textContent = 'Seleccioná un producto antes de guardar.';
            buscar(fila);
        }
    });
});
</script>
@endsection
