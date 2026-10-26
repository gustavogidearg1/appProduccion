@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Editar unidad de producción</h1>

            <p class="text-muted mb-0">
                Plan #{{ $plan->numero }} -
                {{ $planProducto->producto?->nombre ?? 'Producto no disponible' }}
            </p>
        </div>

        <a
            href="{{ route('planes.productos.unidades', [$plan, $planProducto]) }}"
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

            <div class="alert alert-light border mb-4">
                <strong>Unidad:</strong>
                {{ $unidad->numero_unidad }}
            </div>

            <div class="alert alert-info">
                El cliente se muestra desde la venta activa. La serie se carga manualmente en esta unidad.
            </div>

            <form
                method="POST"
                action="{{ route(
                    'planes.productos.unidades.update',
                    [$plan, $planProducto, $unidad]
                ) }}"
            >
                @csrf
                @method('PUT')

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Chasis</label>

                        <input
                            type="text"
                            name="chasis"
                            class="form-control"
                            value="{{ old('chasis', $unidad->chasis) }}"
                            maxlength="100"
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="serie" class="form-label">Serie</label>
                        <input type="text" name="serie" id="serie" class="form-control"
                            value="{{ old('serie', $unidad->serie) }}" maxlength="100">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado</label>

                        <select name="estado" class="form-select" required>
                            @foreach([
                                'planificada' => 'Planificada',
                                'en_produccion' => 'En producción',
                                'terminada' => 'Terminada',
                                'entregada' => 'Entregada',
                                'cancelada' => 'Cancelada',
                            ] as $valor => $texto)
                                <option
                                    value="{{ $valor }}"
                                    @selected(old('estado', $unidad->estado) === $valor)
                                >
                                    {{ $texto }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Color</label>

                        <select name="color" class="form-select">
                            <option value="">Seleccioná un color</option>

                            @foreach($colores as $color)
                                <option
                                    value="{{ $color->nombre }}"
                                    @selected(
                                        old('color', $unidad->color)
                                        === $color->nombre
                                    )
                                >
                                    {{ $color->nombre }}

                                    @if($color->descripcion)
                                        - {{ $color->descripcion }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observaciones</label>

                        <textarea
                            name="observaciones"
                            class="form-control"
                            rows="4"
                        >{{ old('observaciones', $unidad->observaciones) }}</textarea>
                    </div>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                    <a
                        href="{{ route(
                            'planes.productos.unidades',
                            [$plan, $planProducto]
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
