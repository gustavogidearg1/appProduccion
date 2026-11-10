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
    @php
        $mostrarTodas = request()->query('etapas') === 'todas';
        $ordenEtapas = $etapas->values()->pluck('id')->flip();
        $etapasVisibles = $mostrarTodas
            ? $etapas
            : $etapas->reject(fn ($etapa) => $avances->get($etapa->id)?->estado === 'completada');
    @endphp

    <nav class="d-flex align-items-center flex-wrap gap-2 mb-3" aria-label="Filtro de etapas">
        <span class="fw-semibold">Mostrar etapas</span>
        <a href="{{ request()->fullUrlWithQuery(['etapas' => 'activas']) }}"
           class="btn btn-sm {{ $mostrarTodas ? 'btn-outline-primary' : 'btn-primary' }}"
           @if (!$mostrarTodas) aria-current="true" @endif>Pendientes y en proceso</a>
        <a href="{{ request()->fullUrlWithQuery(['etapas' => 'todas']) }}"
           class="btn btn-sm {{ $mostrarTodas ? 'btn-primary' : 'btn-outline-primary' }}"
           @if ($mostrarTodas) aria-current="true" @endif>Mostrar todos</a>
    </nav>

    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">Seguimiento y sectores del plan</h4>
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
                        @forelse($etapasVisibles as $etapa)

                            @php
                                $avance = $avances->get($etapa->id);

                                $etapasAnteriores = $etapas->whereIn('codigo', \App\Services\SectoresProduccion::previas($etapa->codigo));

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
                                $posteriores = $avances->filter(fn($a)=>in_array($a->etapa?->codigo,\App\Services\SectoresProduccion::posteriores($etapa->codigo),true) && ($a->estado!=='pendiente' || $a->fecha_inicio || $a->fecha_fin));
                            @endphp

                            <tr>
                                <td>
                                    {{ $ordenEtapas->get($etapa->id) + 1 }}
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
                                        type="date"
                                        name="fecha_inicio_planificada" @readonly(!auth()->user()->administraAvancesGlobales())
                                        class="form-control form-control-sm"
                                        value="{{ $avance?->fecha_inicio_planificada?->format('Y-m-d') }}"
                                    >
                                </td>

                                <td>
                                    <input
                                        form="{{ $formPlanificacion }}"
                                        type="date"
                                        name="fecha_fin_planificada" @readonly(!auth()->user()->administraAvancesGlobales())
                                        class="form-control form-control-sm"
                                        value="{{ $avance?->fecha_fin_planificada?->format('Y-m-d') }}"
                                    >
                                </td>

                                <td>
                                    <input type="date" form="{{ $formReales }}" name="fecha_inicio" @readonly(!auth()->user()->administraAvancesGlobales()) aria-label="Inicio real de {{ $etapa->nombre }}" class="form-control form-control-sm" value="{{ (string) old('etapa_real') === (string) $etapa->id ? old('fecha_inicio') : $avance?->fecha_inicio?->format('Y-m-d') }}">
                                </td>

                                <td>
                                    <input type="date" form="{{ $formReales }}" name="fecha_fin" @readonly(!auth()->user()->administraAvancesGlobales()) aria-label="Fin real de {{ $etapa->nombre }}" class="form-control form-control-sm" value="{{ (string) old('etapa_real') === (string) $etapa->id ? old('fecha_fin') : $avance?->fecha_fin?->format('Y-m-d') }}">
                                </td>

                                <td>
                                    @if (auth()->user()->administraAvancesGlobales())
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
                                            Guardar planificación
                                        </button>
                                    </form>

                                    <form id="{{ $formReales }}" method="POST" action="{{ route('planes.avances.reales',[$plan,$etapa]) }}" class="mb-2">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="version" value="{{ $versionReales }}">
                                        <input type="hidden" name="etapa_real" value="{{ $etapa->id }}">
                                        @if ($avance?->fecha_inicio || $avance?->fecha_fin)
                                            <details class="mb-2" @if ((string) old('etapa_real') === (string) $etapa->id) open @endif>
                                                <summary>Motivo si corregís una fecha</summary>
                                                <textarea name="motivo" aria-label="Motivo de la corrección de {{ $etapa->nombre }}" class="form-control form-control-sm" maxlength="5000">{{ (string) old('etapa_real') === (string) $etapa->id ? old('motivo') : '' }}</textarea>
                                                @if ($posteriores->isNotEmpty())
                                                    <label class="small d-block mt-2"><input type="checkbox" name="reiniciar_posteriores" value="1"> Si quito el fin real, confirmo devolver las etapas dependientes a pendiente.</label>
                                                @endif
                                            </details>
                                        @endif
                                        <button class="btn btn-sm btn-outline-success">Guardar fechas reales</button>
                                    </form>
                                    @endif
                                    @if (!auth()->user()->puedeOperarEtapaGlobal($etapa))
                                        <span class="text-muted">Solo consulta</span>
                                    @elseif(!$avance || $avance->estado === 'pendiente')

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
                                    {{ $etapas->isEmpty() ? 'No hay etapas grupales configuradas.' : 'Todas las etapas están completadas.' }}
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
{{ $correccionesPlan->appends(request()->only('etapas'))->links('pagination::bootstrap-5') }}
</div></div>
    <div class="mt-4">
    <p class="small text-muted">Se trabaja por día, sin horario. Iniciar y Finalizar registran la fecha de hoy. Para cargar un avance histórico, completá Inicio real y Fin real y elegí Guardar fechas reales. Solo se pide motivo al cambiar o quitar una fecha ya registrada. Sin fechas queda Pendiente; con solo inicio, En proceso; con inicio y fin, Completada. Las fechas planificadas se guardan por separado.</p>
    <div class="alert alert-info small">El Gantt global muestra Corte, Plegado, Mecanizado, Agropartes, Línea de montaje y Prepintura. Planificado y Lanzado conservan sus fechas como seguimiento. Corte y Mecanizado pueden iniciar en paralelo; Montaje requiere Mecanizado y Agropartes completos. Los avances individuales de cada chasis se mantienen por separado.</div>
    </div>
</div>
@endsection
