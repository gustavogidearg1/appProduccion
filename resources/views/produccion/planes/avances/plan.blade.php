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
                                    {{ $avance?->fecha_inicio?->format('d/m/Y H:i') ?? '-' }}
                                </td>

                                <td>
                                    {{ $avance?->fecha_fin?->format('d/m/Y H:i') ?? '-' }}
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

</div>
@endsection
