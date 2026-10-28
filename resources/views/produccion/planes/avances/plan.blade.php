@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Avance general del plan</h1>

            <p class="text-muted mb-0">
                Plan #{{ $plan->numero }}
            </p>
        </div>

        <a
            href="{{ route('planes.show', $plan) }}"
            class="btn btn-outline-secondary"
        >
            Volver al plan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <p class="small text-muted">Podés corregir las fechas reales indicando un motivo. Sin fechas queda Pendiente; con solo inicio, En proceso; con inicio y fin, Completada. Las fechas planificadas se guardan por separado.</p>
    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">Etapas grupales</h4>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Orden</th>
                            <th>Etapa</th>
                            <th>Estado</th>
                            <th>Inicio planificado</th>
                            <th>Fin planificado</th>
                            <th>Inicio real</th>
                            <th>Fin real</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($etapas as $etapa)

                            @php
                                $avance = $avances->get($etapa->id);

                                $etapasAnteriores = $etapas->where(
                                    'orden',
                                    '<',
                                    $etapa->orden
                                );

                                $puedeIniciar = $etapasAnteriores->every(
                                    function ($etapaAnterior) use ($avances) {
                                        $avanceAnterior = $avances->get(
                                            $etapaAnterior->id
                                        );

                                        return $avanceAnterior?->estado === 'completada';
                                    }
                                );

                                $formPlanificacion = 'planificacion-etapa-' . $etapa->id;
                                $formReales = 'reales-etapa-' . $etapa->id;
                                $posteriores = $avances->filter(fn($a)=>$a->etapa?->orden>$etapa->orden && ($a->estado!=='pendiente' || $a->fecha_inicio || $a->fecha_fin));
                            @endphp

                            <tr>
                                <td>
                                    {{ $etapa->orden }}
                                </td>

                                <td class="fw-semibold">
                                    {{ $etapa->nombre }}
                                </td>

                                <td>
                                    @if(!$avance)
                                        <span class="badge bg-secondary">
                                            Pendiente
                                        </span>
                                    @elseif($avance->estado === 'en_proceso')
                                        <span class="badge bg-warning text-dark">
                                            En proceso
                                        </span>
                                    @elseif($avance->estado === 'completada')
                                        <span class="badge bg-success">
                                            Completada
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($avance->estado) }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <input
                                        form="{{ $formPlanificacion }}"
                                        type="datetime-local"
                                        name="fecha_inicio_planificada"
                                        class="form-control form-control-sm"
                                        value="{{ $avance?->fecha_inicio_planificada?->format('Y-m-d\TH:i') }}"
                                    >
                                </td>

                                <td>
                                    <input
                                        form="{{ $formPlanificacion }}"
                                        type="datetime-local"
                                        name="fecha_fin_planificada"
                                        class="form-control form-control-sm"
                                        value="{{ $avance?->fecha_fin_planificada?->format('Y-m-d\TH:i') }}"
                                    >
                                </td>

                                <td>
                                    <input type="datetime-local" step="1" form="{{ $formReales }}" name="fecha_inicio" aria-label="Inicio real de {{ $etapa->nombre }}" class="form-control form-control-sm" value="{{ $avance?->fecha_inicio?->format('Y-m-d\TH:i:s') }}">
                                </td>

                                <td>
                                    <input type="datetime-local" step="1" form="{{ $formReales }}" name="fecha_fin" aria-label="Fin real de {{ $etapa->nombre }}" class="form-control form-control-sm" value="{{ $avance?->fecha_fin?->format('Y-m-d\TH:i:s') }}">
                                </td>

                                <td>
                                    <form
                                        id="{{ $formPlanificacion }}"
                                        method="POST"
                                        action="{{ route('planes.avances.update', [$plan, $etapa]) }}"
                                        class="mb-2"
                                    >
                                        @csrf
                                        @method('PUT')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Guardar fechas
                                        </button>
                                    </form>

                                    <details class="mb-2" style="min-width:190px"><summary class="text-warning-emphasis" style="cursor:pointer">Guardar corrección real</summary>
                                        <form id="{{ $formReales }}" method="POST" action="{{ route('planes.avances.reales',[$plan,$etapa]) }}" class="mt-2">@csrf @method('PUT')
                                            <input type="hidden" name="version" value="{{ $versionReales }}">
                                            <label class="small">Motivo de la corrección<textarea name="motivo" class="form-control form-control-sm" required maxlength="5000"></textarea></label>
                                            @if ($posteriores->isNotEmpty())<label class="small d-block mt-2"><input type="checkbox" name="reiniciar_posteriores" value="1"> Si quito el fin real, confirmo devolver las etapas generales posteriores a pendiente y quitar sus fechas reales. Los avances individuales no se modifican.</label>@endif
                                            <button class="btn btn-sm btn-warning mt-2">Guardar fechas reales</button>
                                        </form>
                                    </details>
                                    @if(!$avance || $avance->estado === 'pendiente')

                                        @if($puedeIniciar)
                                            <form
                                                method="POST"
                                                action="{{ route('planes.avances.iniciar', [$plan, $etapa]) }}"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-primary"
                                                >
                                                    Iniciar
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge bg-secondary">
                                                Bloqueada
                                            </span>
                                        @endif

                                    @elseif($avance->estado === 'en_proceso')

                                        <form
                                            method="POST"
                                            action="{{ route('planes.avances.finalizar', [$plan, $etapa]) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-success"
                                            >
                                                Finalizar
                                            </button>
                                        </form>

                                    @else
                                        <span class="text-success">
                                            Cerrada
                                        </span>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td
                                    colspan="8"
                                    class="text-center py-4 text-muted"
                                >
                                    No hay etapas grupales configuradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

<div class="card mt-4"><div class="card-header">Historial de correcciones reales del plan</div><div class="card-body">
@forelse ($correccionesPlan as $correccion)
@php
    $antes=json_decode($correccion->anterior,true);
    $nuevo=json_decode($correccion->nuevo,true);
@endphp
<details class="border rounded p-2 mb-2"><summary>{{ $correccion->created_at }} · {{ $correccion->etapa_nombre }} · {{ $correccion->responsable }}</summary><p class="mt-2">{{ $correccion->motivo }}</p>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Dato</th><th>Antes</th><th>Después</th></tr></thead><tbody>
@foreach (['estado'=>'Estado','fecha_inicio'=>'Inicio real','fecha_fin'=>'Fin real'] as $campo=>$nombre)<tr><td>{{ $nombre }}</td><td>{{ $antes['etapa'][$campo] ?? '-' }}</td><td>{{ $nuevo['etapa'][$campo] ?? '-' }}</td></tr>@endforeach
</tbody></table></div>
@if (!empty($antes['posteriores']))<p>Se reiniciaron {{ count($antes['posteriores']) }} etapas generales. Fechas anteriores:</p>@foreach ($antes['posteriores'] as $a)<div class="small">Etapa #{{ $a['etapa_id'] }} · {{ $a['estado'] }} · {{ $a['fecha_inicio'] ?? '-' }} → {{ $a['fecha_fin'] ?? '-' }}</div>@endforeach @endif
</details>
@empty<p class="text-muted mb-0">No hay correcciones registradas.</p>@endforelse
{{ $correccionesPlan->links('pagination::bootstrap-5') }}
</div></div>
</div>
@endsection
