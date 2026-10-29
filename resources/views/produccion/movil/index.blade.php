@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <div class="text-center mb-4">
                    <h1 class="mb-2">Producción móvil</h1>
                    <p class="text-muted mb-0">
                        Escaneá el QR de la ficha o buscá por plan y chasis
                    </p>
                </div>

                <div id="lector-qr" class="card shadow-sm mb-4" data-buscar-url="{{ route('produccion.movil') }}">
                    <div class="card-body p-4">
                        <h2 class="h5">Leer QR de la ficha</h2>
                        <p class="small text-muted">Al leer el código se cargan automáticamente el plan, el producto y el chasis. También podés abrir el QR con la cámara del celular.</p>
                        <button type="button" id="qr-iniciar" class="btn btn-success btn-lg w-100"><i class="bi bi-qr-code-scan me-2"></i>Escanear QR con cámara</button>
                        <div id="qr-video-panel" class="mt-3" hidden>
                            <video id="qr-video" playsinline muted style="width:100%;max-height:320px;background:#111;border-radius:8px"></video>
                            <button type="button" id="qr-detener" class="btn btn-outline-secondary w-100 mt-2">Cerrar cámara</button>
                        </div>
                        <canvas id="qr-canvas" hidden></canvas>
                        <div id="qr-mensaje" class="small mt-2" role="status" aria-live="polite"></div>

                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">

                        <form method="GET" action="{{ route('produccion.movil') }}">

                            <div class="mb-3">
                                <label for="plan" class="form-label fw-semibold">
                                    Número de plan
                                </label>

                                <input type="number" id="plan" name="plan" class="form-control form-control-lg"
                                    value="{{ $plan?->numero ?? request('plan') }}" placeholder="Ejemplo: 504" required>
                            </div>

                            <div class="mb-4">
                                <label for="chasis" class="form-label fw-semibold">
                                    Número de chasis
                                </label>

                                <input type="text" id="chasis" name="chasis" class="form-control form-control-lg"
                                    value="{{ $unidad?->chasis ?? request('chasis') }}" placeholder="Ejemplo: 20517" required>
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
                                    {{ $unidad->estimacion_entrega['excluida'] ? $unidad->estimacion_entrega['texto'] : $unidad->estimacion_entrega['etapa'] }}
                                </span>
                            </div>

                            @if (!$unidad->estimacion_entrega['excluida'])
                                <div class="small text-muted mb-3">{{ $unidad->estimacion_entrega['origen'] === 'plan' ? 'Etapa del plan' : 'Etapa del chasis' }}</div>
                            @endif
                            @if ($etapaActual)
                                <div class="alert alert-info">
                                    <strong>{{ $etapaActual->estado === 'en_proceso' ? 'Avance individual en curso:' : 'Próximo avance individual:' }}</strong>
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
@if ($unidad->estado !== 'entregada')
    <a href="{{ route(
        'planes.productos.unidades.edit',
        [
            $plan,
            $unidad->planProducto,
            $unidad
        ]
    ) }}"
       class="btn btn-outline-secondary btn-lg">
        Editar unidad
    </a>
@else
    <div class="alert alert-success text-center mb-0">
        <i class="bi bi-check-circle me-1"></i>
        Unidad entregada. Solo consulta.
    </div>
@endif

                            </div>

                        </div>
                    </div>
                @endif

            </div>
        </div>

    </div>
    <script src="{{ asset('vendor/jsqr/jsQR.js') }}" defer></script>
    <script src="{{ asset('js/produccion-qr.js') }}" defer></script>
@endsection
