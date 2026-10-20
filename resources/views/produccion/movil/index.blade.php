@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <div class="text-center mb-4">
                    <h1 class="mb-2">Producción móvil</h1>
                    <p class="text-muted mb-0">
                        Buscá una unidad por plan y chasis
                    </p>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">

                        <form method="GET" action="{{ route('produccion.movil') }}">

                            <div class="mb-3">
                                <label for="plan" class="form-label fw-semibold">
                                    Número de plan
                                </label>

                                <input type="number" id="plan" name="plan" class="form-control form-control-lg"
                                    value="{{ request('plan') }}" placeholder="Ejemplo: 504" required>
                            </div>

                            <div class="mb-4">
                                <label for="chasis" class="form-label fw-semibold">
                                    Número de chasis
                                </label>

                                <input type="text" id="chasis" name="chasis" class="form-control form-control-lg"
                                    value="{{ request('chasis') }}" placeholder="Ejemplo: 20517" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                Buscar unidad
                            </button>
                        </form>

                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($mensaje)
                    <div class="alert alert-warning">
                        {{ $mensaje }}
                    </div>
                @endif

                @if ($unidad)
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h2 class="h4 mb-0">Unidad encontrada</h2>
                        </div>

                        <div class="card-body">

                            <div class="mb-3">
                                <div class="text-muted small">Plan</div>
                                <div class="fs-5 fw-semibold">
                                    #{{ $plan->numero }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-muted small">Producto</div>
                                <div class="fs-5 fw-semibold">
                                    {{ $unidad->planProducto?->producto?->nombre ?? '-' }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-muted small">Chasis</div>
                                <div class="fs-5 fw-semibold">
                                    {{ $unidad->chasis }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="text-muted small">Estado</div>

                                <span class="badge bg-primary fs-6">
                                    {{ ucfirst(str_replace('_', ' ', $unidad->estado)) }}
                                </span>
                            </div>

                            @if ($etapaActual)
                                <div class="alert alert-info">
                                    <strong>Etapa actual:</strong>
                                    {{ $etapaActual->etapa?->nombre ?? '-' }}
                                </div>
                            @endif

                            @if ($etapaActual && $etapaActual->estado !== 'completada')
                                <div class="card border-primary mb-4">
                                    <div class="card-body">

                                        <div class="text-muted small">
                                            Acción pendiente
                                        </div>

                                        <h3 class="h5 mb-3">
                                            {{ $etapaActual->etapa?->nombre ?? 'Etapa actual' }}
                                        </h3>

                                        @if ($etapaActual->estado === 'en_proceso')
                                            <form method="POST"
                                                action="{{ route('planes.productos.unidades.avances.finalizar', [
                                                    $plan,
                                                    $unidad->planProducto,
                                                    $unidad,
                                                    $etapaActual->etapa,
                                                ]) }}">
                                                @csrf

                                                <button type="submit" class="btn btn-success btn-lg w-100">
                                                    Finalizar etapa
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST"
                                                action="{{ route('planes.productos.unidades.avances.iniciar', [
                                                    $plan,
                                                    $unidad->planProducto,
                                                    $unidad,
                                                    $etapaActual->etapa,
                                                ]) }}">
                                                @csrf

                                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                                    Iniciar etapa
                                                </button>
                                            </form>
                                        @endif

                                    </div>
                                </div>
                            @endif

                            <div class="d-grid gap-2">

                                <a href="{{ route('planes.productos.unidades.avances', [$plan, $unidad->planProducto, $unidad]) }}"
                                    class="btn btn-success btn-lg">
                                    Avances de producción
                                </a>

                                <a href="{{ route('planes.productos.unidades.calidad', [$plan, $unidad->planProducto, $unidad]) }}"
                                    class="btn btn-primary btn-lg">
                                    Control de calidad
                                </a>

                                <a href="{{ route('planes.productos.unidades.componentes.index', [$plan, $unidad->planProducto, $unidad]) }}"
                                    class="btn btn-outline-primary btn-lg">
                                    Componentes y trazabilidad
                                </a>

                                <a href="{{ route('planes.productos.unidades.edit', [$plan, $unidad->planProducto, $unidad]) }}"
                                    class="btn btn-outline-secondary btn-lg">
                                    Editar unidad
                                </a>

                            </div>

                        </div>
                    </div>
                @endif

            </div>
        </div>

    </div>
@endsection
