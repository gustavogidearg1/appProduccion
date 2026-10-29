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

    <form id="crear-venta-form" method="POST" action="{{ route('ventas.store') }}">
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

                    <div class="col-md-4"><label class="form-label" for="fecha_entrega_pactada">Entrega pactada / solicitada</label><input type="date" id="fecha_entrega_pactada" name="fecha_entrega_pactada" class="form-control" value="{{ old('fecha_entrega_pactada') }}"><div class="small text-muted">Opcional. Compromiso con el cliente; separado de la estimación de producción.</div></div>
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

                    @foreach ($detallesIniciales as $indice => $detalleInicial)
                    @php
                        $unidadFila = $unidadesFormulario->get($detalleInicial['unidad_produccion_id'] ?? null);
                        $productoFila = $productos->firstWhere('id', $detalleInicial['producto_id'] ?? null);
                    @endphp
                    <div class="producto-row border rounded p-3 mb-3" data-unidad="{{ $unidadFila?->id }}">
                        <div class="row g-3 align-items-end">

                            <div class="col-md-5">
                                <label class="form-label">
                                    Producto
                                </label>
                                <input
    type="hidden"
    name="detalles[{{ $indice }}][unidad_produccion_id]"
    value="{{ $detalleInicial['unidad_produccion_id'] ?? '' }}"
>

                                @if ($unidadFila)
                                    <p class="text-muted">Chasis seleccionado: <strong>{{ $unidadFila->chasis }}</strong></p>
                                    <div class="border rounded bg-light p-2 mb-3">
                                        <strong>Entrega estimada actual</strong>
                                        @include('planificacion.estimacion', ['estimacion'=>$unidadFila->estimacion_entrega])
                                    </div>

                                @endif
                                <input type="hidden" class="venta-producto-id" name="detalles[{{ $indice }}][producto_id]" value="{{ $detalleInicial['producto_id'] ?? '' }}">
                                <div class="d-flex flex-wrap gap-2">
                                    <div class="form-control bg-light venta-producto-resumen" style="width:auto;flex:1;min-width:160px" aria-live="polite">@if ($productoFila){{ $productoFila->codigo }} - {{ $productoFila->nombre }}@else Seleccionar producto @endif</div>
                                    @if (!$unidadFila)<button type="button" class="btn btn-outline-primary buscar-producto"><i class="bi bi-search me-1"></i>Buscar producto</button>@endif
                                </div>
                                <div class="small text-danger error-producto" role="alert"></div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Cantidad
                                </label>

                                <input
                                    type="number"
                                    name="detalles[{{ $indice }}][cantidad]"
                                    @readonly($unidadFila !== null)
                                    class="form-control"
                                    min="0.01"
                                    step="0.01"
                                    value="{{ $detalleInicial['cantidad'] ?? 1 }}"
                                    required
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    Precio unitario
                                </label>

                                <input
                                    type="number"
                                    name="detalles[{{ $indice }}][precio_unitario]"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="{{ $detalleInicial['precio_unitario'] ?? '' }}"
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

                    @endforeach
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

@include('components.buscador-productos', ['productos'=>$productos])
<template id="detalle-venta-template">
<div class="producto-row border rounded p-3 mb-3" data-unidad=""><div class="row g-3 align-items-end">
<div class="col-md-5"><label class="form-label">Producto</label>
<input type="hidden" name="detalles[__INDEX__][unidad_produccion_id]" value="">
<input type="hidden" name="detalles[__INDEX__][producto_id]" class="venta-producto-id" value="">
<div class="d-flex flex-wrap gap-2"><div class="form-control bg-light venta-producto-resumen" style="width:auto;flex:1;min-width:160px" aria-live="polite">Seleccionar producto</div><button type="button" class="btn btn-outline-primary buscar-producto"><i class="bi bi-search me-1"></i>Buscar producto</button></div>
<div class="small text-danger error-producto" role="alert"></div></div>
<div class="col-md-2"><label class="form-label">Cantidad</label><input type="number" name="detalles[__INDEX__][cantidad]" class="form-control" min="0.01" step="0.01" value="1" required></div>
<div class="col-md-3"><label class="form-label">Precio unitario</label><input type="number" name="detalles[__INDEX__][precio_unitario]" class="form-control" min="0" step="0.01" required></div>
<div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 quitar-producto">Quitar</button></div>
</div></div>
</template>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let indiceProducto = {{ max([0, ...array_map('intval', array_keys($detallesIniciales))]) + 1 }};
    const contenedor = document.getElementById('productos-container');
    const template = document.getElementById('detalle-venta-template');
    document.getElementById('agregar-producto').addEventListener('click', () => {
        contenedor.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', indiceProducto++));
        window.buscadorProductos.abrir(contenedor.lastElementChild);
    });
    contenedor.addEventListener('click', evento => {
        const boton = evento.target.closest('button');
        if (!boton) return;
        const fila = boton.closest('.producto-row');
        if (boton.classList.contains('buscar-producto') && !fila.dataset.unidad) window.buscadorProductos.abrir(fila);
        if (boton.classList.contains('quitar-producto') && contenedor.querySelectorAll('.producto-row').length > 1) fila.remove();
    });
    contenedor.addEventListener('producto-seleccionado', evento => {
        const fila = evento.target.closest('.producto-row');
        if (!fila || fila.dataset.unidad) return;
        fila.querySelector('.venta-producto-id').value = evento.detail.id;
        fila.querySelector('.venta-producto-resumen').textContent = `${evento.detail.codigo ? evento.detail.codigo + ' - ' : ''}${evento.detail.nombre}`;
        fila.querySelector('.error-producto').textContent = '';
    });
    document.getElementById('crear-venta-form').addEventListener('submit', evento => {
        const fila = [...contenedor.querySelectorAll('.producto-row')].find(f => !f.querySelector('.venta-producto-id').value);
        if (fila) {
            evento.preventDefault();
            fila.querySelector('.error-producto').textContent = 'Seleccioná un producto antes de guardar.';
            window.buscadorProductos.abrir(fila);
        }
    });
});
</script>
@endsection
