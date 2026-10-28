@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>Avance de producción</h1>

            <p class="text-muted mb-0">
                Plan #{{ $plan->numero }} -
                Unidad {{ $unidad->numero_unidad }} -
                {{ $planProducto->producto?->nombre ?? '-' }}
            </p>
        </div>

        <a href="{{ route('entregas.index',$unidad) }}" class="btn btn-success">Control de entrega y fotos</a>
        <a href="{{ route('planes.productos.unidades', [$plan, $planProducto]) }}"
           class="btn btn-outline-secondary">
            Volver
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Vista para PC --}}
    <div class="card shadow-sm d-none d-md-block">

        <div class="card-header">
            <h2 class="h4 mb-0">
                Etapas de la unidad
            </h2>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Orden</th>
                        <th>Etapa</th>
                        <th>Estado</th>
                        <th>Inicio</th>
                        <th>Finalización</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($etapas as $etapa)
                        @php
                            $avance = $avances->get($etapa->id);

                            $etapasAnteriores = $etapas->where(
                                'orden',
                                '<',
                                $etapa->orden
                            );

                            $puedeIniciar = $etapasAnteriores->every(
                                function ($etapaAnterior) use ($avances) {
                                    return $avances->get(
                                        $etapaAnterior->id
                                    )?->estado === 'completada';
                                }
                            );
                        @endphp

                        <tr>
                            <td>{{ $etapa->orden }}</td>

                            <td class="fw-semibold">
                                {{ $etapa->nombre }}
                            </td>

                            <td>
                                @if (!$avance)
                                    <span class="badge bg-secondary">
                                        Pendiente
                                    </span>
                                @elseif ($avance->estado === 'en_proceso')
                                    <span class="badge bg-warning text-dark">
                                        En proceso
                                    </span>
                                @elseif ($avance->estado === 'completada')
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
                                {{ $avance?->fecha_inicio?->format('d/m/Y H:i') ?? '-' }}
                            </td>

                            <td>
                                {{ $avance?->fecha_fin?->format('d/m/Y H:i') ?? '-' }}
                            </td>

                            <td>
                                <a href="{{ route('avances.correccion.edit',[$plan,$planProducto,$unidad,$etapa]) }}" class="btn btn-sm btn-outline-warning mb-2">Corregir avance</a>
                                @if (!$avance || $avance->estado === 'pendiente')

                                    @if ($puedeIniciar)
                                        <form method="POST"
                                              action="{{ route(
                                                  'planes.productos.unidades.avances.iniciar',
                                                  [$plan, $planProducto, $unidad, $etapa]
                                              ) }}">
                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-sm btn-primary">
                                                Iniciar
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary">
                                            Bloqueada
                                        </span>
                                    @endif

                                @elseif ($avance->estado === 'en_proceso')

                                    <form method="POST"
                                          action="{{ route(
                                              'planes.productos.unidades.avances.finalizar',
                                              [$plan, $planProducto, $unidad, $etapa]
                                          ) }}">
                                        @csrf

                                        <button type="submit"
                                                class="btn btn-sm btn-success">
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
                            <td colspan="6"
                                class="text-center py-4 text-muted">
                                No hay etapas configuradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vista para celular --}}
    <div class="d-md-none">

        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    Etapas de la unidad
                </h2>
            </div>

            <div class="card-body p-2">
                @forelse ($etapas as $etapa)
                    @php
                        $avance = $avances->get($etapa->id);

                        $etapasAnteriores = $etapas->where(
                            'orden',
                            '<',
                            $etapa->orden
                        );

                        $puedeIniciar = $etapasAnteriores->every(
                            function ($etapaAnterior) use ($avances) {
                                return $avances->get(
                                    $etapaAnterior->id
                                )?->estado === 'completada';
                            }
                        );
                    @endphp

                    <div class="border rounded p-3 mb-3">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="small text-muted">
                                    Orden {{ $etapa->orden }}
                                </div>

                                <h3 class="h5 mb-0">
                                    {{ $etapa->nombre }}
                                </h3>
                            </div>

                            @if (!$avance)
                                <span class="badge bg-secondary">
                                    Pendiente
                                </span>
                            @elseif ($avance->estado === 'en_proceso')
                                <span class="badge bg-warning text-dark">
                                    En proceso
                                </span>
                            @else
                                <span class="badge bg-success">
                                    Completada
                                </span>
                            @endif
                        </div>

                        <div class="small text-muted mb-3">
                            Inicio:
                            {{ $avance?->fecha_inicio?->format('d/m/Y H:i') ?? '-' }}
                            <br>

                            Finalización:
                            {{ $avance?->fecha_fin?->format('d/m/Y H:i') ?? '-' }}
                        </div>

                        <a href="{{ route('avances.correccion.edit',[$plan,$planProducto,$unidad,$etapa]) }}" class="btn btn-outline-warning mb-2">Corregir avance</a>
                        @if (!$avance || $avance->estado === 'pendiente')

                            @if ($puedeIniciar)
                                <form method="POST"
                                      action="{{ route(
                                          'planes.productos.unidades.avances.iniciar',
                                          [$plan, $planProducto, $unidad, $etapa]
                                      ) }}">
                                    @csrf

                                    <button type="submit"
                                            class="btn btn-primary btn-lg w-100">
                                        Iniciar etapa
                                    </button>
                                </form>
                            @else
                                <div class="text-center text-muted">
                                    <i class="bi bi-lock me-1"></i>
                                    Etapa bloqueada
                                </div>
                            @endif

                        @elseif ($avance->estado === 'en_proceso')

                            <form method="POST"
                                  action="{{ route(
                                      'planes.productos.unidades.avances.finalizar',
                                      [$plan, $planProducto, $unidad, $etapa]
                                  ) }}">
                                @csrf

                                <button type="submit"
                                        class="btn btn-success btn-lg w-100">
                                    Finalizar etapa
                                </button>
                            </form>

                        @else
                            <div class="text-success fw-semibold">
                                <i class="bi bi-check-circle me-1"></i>
                                Etapa cerrada
                            </div>
                        @endif

                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        No hay etapas configuradas.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

<div class="card mt-4"><div class="card-header"><h2 class="h5 mb-0">Historial de correcciones</h2></div><div class="card-body">
@forelse ($correcciones as $correccion)
<details class="border rounded p-2 mb-2"><summary>{{ $correccion->created_at }} · {{ $correccion->etapa_nombre }} · {{ $correccion->responsable }}</summary>
<p class="mt-2" style="white-space: pre-line">{{ $correccion->motivo }}</p>
@php
    $antes = json_decode($correccion->anterior,true);
    $despues = json_decode($correccion->nuevo,true);
@endphp
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Dato</th><th>Antes</th><th>Después</th></tr></thead><tbody>
@foreach (['estado'=>'Estado','fecha_inicio'=>'Inicio real','fecha_fin'=>'Fin real','observaciones'=>'Observaciones'] as $campo=>$nombre)<tr><td>{{ $nombre }}</td><td>{{ $antes['etapa'][$campo] ?? '-' }}</td><td>{{ $despues['etapa'][$campo] ?? '-' }}</td></tr>@endforeach
<tr><td>Estado de la unidad</td><td>{{ $antes['estado_unidad'] }}</td><td>{{ $despues['estado_unidad'] }}</td></tr>
</tbody></table></div>
@if (!empty($antes['posteriores']))<p>Se reiniciaron {{ count($antes['posteriores']) }} etapas posteriores. Valores anteriores:</p>
@foreach ($antes['posteriores'] as $anterior)<div class="small">Etapa #{{ $anterior['etapa_id'] }} · {{ $anterior['estado'] }} · Inicio: {{ $anterior['fecha_inicio'] ?? '-' }} · Fin: {{ $anterior['fecha_fin'] ?? '-' }}</div>@endforeach
@endif
</details>
@empty<p class="text-muted mb-0">No hay correcciones registradas.</p>@endforelse
{{ $correcciones->links('pagination::bootstrap-5') }}
</div></div>
</div>
@endsection
