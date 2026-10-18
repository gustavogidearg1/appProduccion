@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Editar componente</h1>

            <p class="text-muted mb-0">
                Unidad {{ $unidad->numero_unidad }} -
                {{ $componente->producto?->nombre ?? 'Producto no disponible' }}
            </p>
        </div>

        <a
            href="{{ route(
                'planes.productos.unidades.componentes.index',
                [$plan, $planProducto, $unidad]
            ) }}"
            class="btn btn-outline-secondary"
        >
            Volver
        </a>
    </div>

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

            <div class="alert alert-light border">
                <strong>Componente:</strong>
                {{ $componente->producto?->nombre ?? '-' }}
            </div>

            <form
                method="POST"
                action="{{ route(
                    'planes.productos.unidades.componentes.update',
                    [$plan, $planProducto, $unidad, $componente]
                ) }}"
            >
                @csrf
                @method('PUT')

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Cantidad</label>

                        <input
                            type="number"
                            name="cantidad"
                            class="form-control"
                            value="{{ old('cantidad', $componente->cantidad) }}"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado</label>

                        <select name="estado" class="form-select" required>
                            @foreach([
                                'pendiente' => 'Pendiente',
                                'instalado' => 'Instalado',
                                'no corresponde' => 'No corresponde',
                                'retirado' => 'Retirado',
                            ] as $valor => $texto)
                                <option
                                    value="{{ $valor }}"
                                    @selected(
                                        old('estado', $componente->estado)
                                        === $valor
                                    )
                                >
                                    {{ $texto }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Uso</label>

                        <select name="uso" class="form-select" required>
                            <option
                                value="definitivo"
                                @selected(
                                    old('uso', $componente->uso)
                                    === 'definitivo'
                                )
                            >
                                Definitivo
                            </option>

                            <option
                                value="exhibicion"
                                @selected(
                                    old('uso', $componente->uso)
                                    === 'exhibicion'
                                )
                            >
                                Exhibición
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Número de trazabilidad
                        </label>

                        <input
                            type="text"
                            name="trazabilidad"
                            class="form-control"
                            value="{{ old(
                                'trazabilidad',
                                $componente->trazabilidad
                            ) }}"
                            maxlength="150"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Fecha de instalación
                        </label>

                        <input
                            type="date"
                            name="fecha_instalacion"
                            class="form-control"
                            value="{{ old(
                                'fecha_instalacion',
                                $componente->fecha_instalacion?->format('Y-m-d')
                            ) }}"
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observaciones</label>

                        <textarea
                            name="observaciones"
                            class="form-control"
                            rows="4"
                        >{{ old(
                            'observaciones',
                            $componente->observaciones
                        ) }}</textarea>
                    </div>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
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
