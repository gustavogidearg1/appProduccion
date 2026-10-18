@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <h1 class="mb-4">Agregar componente</h1>

    <p class="text-muted">
        Plan #{{ $plan->numero }} -
        Unidad {{ $unidad->numero_unidad }}
    </p>

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

            <form
                method="POST"
                action="{{ route(
                    'planes.productos.unidades.componentes.store',
                    [$plan, $planProducto, $unidad]
                ) }}"
            >
                @csrf

                <div class="row g-3">

                    <div class="col-md-8">
                        <label class="form-label">Producto o accesorio</label>

                        <select
                            name="producto_id"
                            class="form-select"
                            required
                        >
                            <option value="">Seleccioná un producto</option>

                            @foreach($productos as $producto)
                                <option
                                    value="{{ $producto->id }}"
                                    @selected(old('producto_id') == $producto->id)
                                >
                                    {{ $producto->codigo }} -
                                    {{ $producto->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cantidad</label>

                        <input
                            type="number"
                            name="cantidad"
                            class="form-control"
                            value="{{ old('cantidad', 1) }}"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado</label>

                        <select name="estado" class="form-select" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="instalado">Instalado</option>
                            <option value="no corresponde">No corresponde</option>
                            <option value="retirado">Retirado</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Uso</label>

                        <select name="uso" class="form-select" required>
                            <option value="definitivo">Definitivo</option>
                            <option value="exhibicion">Exhibición</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha de instalación</label>

                        <input
                            type="date"
                            name="fecha_instalacion"
                            class="form-control"
                            value="{{ old('fecha_instalacion') }}"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Número de trazabilidad
                        </label>

                        <input
                            type="text"
                            name="trazabilidad"
                            class="form-control"
                            value="{{ old('trazabilidad') }}"
                            maxlength="150"
                            placeholder="Ejemplo: BAL-2026-001"
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observaciones</label>

                        <textarea
                            name="observaciones"
                            class="form-control"
                            rows="4"
                        >{{ old('observaciones') }}</textarea>
                    </div>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Guardar componente
                    </button>

                    <a
                        href="{{ route(
                            'planes.productos.unidades.componentes.index',
                            [$plan, $planProducto, $unidad]
                        ) }}"
                        class="btn btn-outline-secondary"
                    >
                        Cancelar
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection
