@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <h1 class="mb-2">Agregar componente estándar</h1>

    <p class="text-muted">
        Producto: {{ $producto->codigo }} - {{ $producto->nombre }}
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
                action="{{ route('productos.componentes.store', $producto) }}"
            >
                @csrf

                <div class="row g-3">

                    <div class="col-md-8">
                        <label class="form-label">Componente o accesorio</label>

                        <select
                            name="componente_producto_id"
                            class="form-select"
                            required
                        >
                            <option value="">
                                Seleccioná un componente
                            </option>

                            @foreach($componentes as $componente)
                                <option
                                    value="{{ $componente->id }}"
                                    @selected(
                                        old('componente_producto_id')
                                        == $componente->id
                                    )
                                >
                                    {{ $componente->codigo }} -
                                    {{ $componente->nombre }}
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
                        <label class="form-label">¿Es obligatorio?</label>

                        <select name="obligatorio" class="form-select" required>
                            <option value="0">No</option>
                            <option value="1">Sí</option>
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
                        <label class="form-label">Activo</label>

                        <select name="activo" class="form-select" required>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
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
                    <button class="btn btn-primary">
                        Guardar componente
                    </button>

                    <a
                        href="{{ route('productos.componentes.index', $producto) }}"
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
