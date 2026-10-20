@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="mb-1">Control de calidad</h1>

            <p class="text-muted mb-0">
                Plan #{{ $plan->numero }}
                -
                Unidad {{ $unidad->numero_unidad }}
                -
                {{ $planProducto->producto?->nombre }}
            </p>
        </div>

        <a href="{{ route('planes.productos.unidades', [$plan, $planProducto]) }}"
           class="btn btn-outline-secondary">
            Volver a unidades
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($controles->isEmpty())
        <div class="alert alert-info">
            Este producto no tiene controles de calidad configurados.
        </div>
    @else
        <form method="POST"
              action="{{ route('planes.productos.unidades.calidad.update', [$plan, $planProducto, $unidad]) }}">
            @csrf
            @method('PUT')

            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="h4 mb-0">Controles de la unidad</h2>
                </div>

                <div class="card-body">
                    @foreach ($controles as $controlUnidad)
                        @php
                            $control = $controlUnidad->control;
                            $controlId = $controlUnidad->id;
                        @endphp

                        <div class="border rounded p-3 mb-3">
                            <div class="row g-3">

                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">
                                        {{ $control?->nombre ?? 'Control' }}

                                        @if ($control?->obligatorio)
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>

                                    @if ($control?->descripcion)
                                        <div class="small text-muted">
                                            {{ $control->descripcion }}
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Resultado</label>

                                    <select
                                        name="controles[{{ $controlId }}][resultado]"
                                        class="form-select"
                                    >
                                        @foreach ([
                                            'pendiente' => 'Pendiente',
                                            'aprobado' => 'Aprobado',
                                            'observado' => 'Observado',
                                            'no_aplica' => 'No aplica',
                                        ] as $valor => $texto)
                                            <option
                                                value="{{ $valor }}"
                                                @selected(
                                                    old(
                                                        "controles.$controlId.resultado",
                                                        $controlUnidad->resultado
                                                    ) === $valor
                                                )
                                            >
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Valor o detalle
                                    </label>

                                    <input
                                        type="text"
                                        name="controles[{{ $controlId }}][valor]"
                                        class="form-control"
                                        value="{{ old(
                                            "controles.$controlId.valor",
                                            $controlUnidad->valor
                                        ) }}"
                                        placeholder="Ejemplo: OK, 11112, texto..."
                                    >
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Observaciones
                                    </label>

                                    <textarea
                                        name="controles[{{ $controlId }}][observaciones]"
                                        class="form-control"
                                        rows="2"
                                    >{{ old(
                                        "controles.$controlId.observaciones",
                                        $controlUnidad->observaciones
                                    ) }}</textarea>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Guardar control de calidad
                    </button>

                    <a href="{{ route('planes.productos.unidades', [$plan, $planProducto]) }}"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    @endif

</div>
@endsection
