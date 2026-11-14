@extends('layouts.app')
@section('title', 'Tablero de producción')
@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
            <div>
                <h1 class="fw-bold">Tablero de producción</h1>
                <p class="text-muted mb-0">Estados reales, compromiso comercial y planificación</p>
            </div><span class="badge bg-success">Datos del sistema · {{ $hoy->format('d/m/Y') }}</span>
        </div>
        @include('planificacion.mensajes')
        @include('partials.mis-hallazgos')
        <details class="card mb-4">
            <summary class="card-header fw-semibold">Filtros del tablero</summary>
            <form method="GET" action="{{ route('dashboard') }}">
                <div class="card-body row g-3 align-items-end">
                    @foreach (['sector_gantt', 'plan_gantt', 'numero_plan_gantt', 'situacion_gantt', 'mostrar_fuera_periodo'] as $clave)
                        @if (!empty($filtros[$clave]))
                            <input type="hidden" name="{{ $clave }}" value="{{ $filtros[$clave] }}">
                        @endif
                    @endforeach

                    <div class="col-md-3"><label class="form-label" for="estado-tablero">Estado del plan</label><select
                            class="form-select" name="estado" id="estado-tablero">
                            <option value="">Todos</option>
                            @foreach (['abierto' => 'Abierto', 'cerrado' => 'Cerrado', 'cancelado' => 'Cancelado'] as $valor => $nombre)
                                <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label" for="desde-gantt">Gantt desde</label><input
                            type="date" name="desde" id="desde-gantt" class="form-control"
                            value="{{ $filtros['desde'] }}" required></div>
                    <div class="col-md-3"><label class="form-label" for="hasta-gantt">Gantt hasta</label><input
                            type="date" name="hasta" id="hasta-gantt" class="form-control"
                            value="{{ $filtros['hasta'] }}" required></div>
                    <div class="col-md-3">
                        <label class="form-label" for="numero-plan-tablero">Número de plan</label>
                        <input type="number" min="1" max="4294967295" class="form-control" id="numero-plan-tablero"
                            name="numero_plan" value="{{ $filtros['numero_plan'] ?? '' }}" placeholder="Ej. 709">
                    </div>
                    @foreach (['chasis' => 'Chasis', 'serie' => 'Serie'] as $campo => $etiqueta)
                        <div class="col-md-3">
                            <label class="form-label" for="tablero-{{ $campo }}">{{ $etiqueta }}</label>
                            <input class="form-control" id="tablero-{{ $campo }}" name="{{ $campo }}"
                                value="{{ $filtros[$campo] ?? '' }}">
                        </div>
                    @endforeach
                    <div class="col-md-3">
                        <x-buscador-clientes :clientes="$clientes" :seleccion="$filtros['cliente_id'] ?? null" :texto="$filtros['cliente'] ?? ''" />
                    </div>
                    <div class="col-md-3">
                        <label for="producto-tablero" class="form-label">Producto</label>
                        <input type="hidden" name="producto_id" id="producto-id-tablero"
                            value="{{ $filtros['producto_id'] ?? '' }}">
                        <div class="input-group" id="producto-filtro-tablero">
                            <input type="search" name="buscar_producto" id="producto-tablero" class="form-control"
                                maxlength="255" placeholder="Nombre o código"
                                value="{{ $productos->firstWhere('id', $filtros['producto_id'] ?? null)?->nombre ?? ($filtros['buscar_producto'] ?? '') }}">
                            <button type="button" class="btn btn-outline-primary"
                                onclick="window.buscadorProductos.abrir(this.parentElement)" aria-label="Buscar producto"
                                aria-haspopup="dialog" title="Buscar producto">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="comercial-tablero">Situación comercial</label>
                        <select class="form-select" id="comercial-tablero" name="comercial">
                            <option value="">Todas</option>
                            <option value="stock" @selected(($filtros['comercial'] ?? '') === 'stock')>Stock · sin cliente ni venta</option>
                            <option value="con_cliente" @selected(($filtros['comercial'] ?? '') === 'con_cliente')>Con cliente o venta vinculada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="etapa-tablero">Etapa productiva</label>
                        <select class="form-select" id="etapa-tablero" name="etapa">
                            <option value="">Todas</option>
                            @foreach (collect($tarjetas)->pluck('nombre')->push($filtros['etapa'] ?? null)->filter()->unique() as $nombre)
                                <option value="{{ $nombre }}" @selected(($filtros['etapa'] ?? '') === $nombre)>{{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" name="mostrar_entregadas"
                                id="mostrar-entregadas" value="1" @checked(!empty($filtros['mostrar_entregadas']))>
                            <label class="form-check-label" for="mostrar-entregadas">Mostrar entregadas</label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary">Aplicar</button><a
                            href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Limpiar</a></div>
                    <p class="small text-muted mb-0">El estado filtra todo el tablero. Las fechas cambian solo la ventana
                        del Gantt; no ocultan planes ni modifican su planificación.</p>
                </div>
            </form>
        </details>
        <p class="small text-muted">Cada chasis se cuenta una sola vez en su etapa actual. Hasta iniciar un avance
            individual, se usa la etapa general del plan. Las ventas anuladas y las asignaciones liberadas no cuentan como
            compromiso comercial.</p>
        @php
            $grupos = [
                'Planeado / Lanzado' => ['Planificado', 'Planificada', 'Lanzado'],
                'Inicio de producción' => ['Corte', 'Plegado', 'Mezanizado', 'Mecanizado'],
                'Armado' => ['Armado', 'Agropartes', 'Línea de montaje'],
                'Pintura' => ['Prepintura', 'Escoriado', 'Lavado', 'Sellado', 'Fondo', 'Pintura'],
                'Final' => ['Montaje Final', 'Terminado', 'OK Calidad', 'Entregada'],
            ];
            $porEtapa = collect($tarjetas)->keyBy('nombre');
            $otras = $porEtapa->keys()->diff(collect($grupos)->flatten());
            if ($otras->isNotEmpty()) {
                $grupos['Otras etapas'] = $otras;
            }
            $filtrosTarjeta = array_diff_key($filtros, array_flip(['etapa', 'page']));
        @endphp
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-4">
            @foreach ($grupos as $grupo => $nombres)
                @php
                    $etapasGrupo = $porEtapa->only($nombres);
                @endphp
                @if ($etapasGrupo->isNotEmpty())
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <h2 class="card-header h6 mb-0">{{ $grupo }}</h2>
                            <div class="list-group list-group-flush">
                                @foreach ($etapasGrupo as $nombre => $tarjeta)
                                    @php
                                        $seleccionada = ($filtros['etapa'] ?? '') === $nombre;
                                    @endphp
                                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2 {{ $seleccionada ? 'active' : '' }}"
                                        @if ($seleccionada) aria-current="true" @endif
                                        href="{{ route('dashboard', $seleccionada ? $filtrosTarjeta : $filtrosTarjeta + ['etapa' => $nombre]) }}#resultados-tablero">
                                        {{ $nombre }}
                                        <span
                                            class="badge rounded-pill {{ $seleccionada ? 'bg-light text-dark' : 'bg-secondary' }}">{{ $tarjeta['cantidad'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        @if ($unidadesVencidas->isNotEmpty())
            <details class="alert alert-warning">
                <summary class="fw-semibold">
                    Fecha planificada vencida · {{ $unidadesVencidas->count() }}
                    {{ $unidadesVencidas->count() === 1 ? 'unidad' : 'unidades' }} · Ver detalle
                </summary>
                <p class="small mt-2">Ya pasó el fin planificado del plan o la fecha prevista de entrega de la unidad. La
                    estimación actual puede haberse recalculado.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Chasis</th>
                                <th>Etapa</th>
                                <th>Fin del plan</th>
                                <th>Fecha estimada original</th>
                                <th>Estimación actual</th>
                                <th>Demoras registradas</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unidadesVencidas as $unidad)
                                @php
                                    $linea = $unidad->planProducto;
                                    $plan = $linea->plan;
                                    $eta = $unidad->estimacion_entrega;
                                @endphp
                                <tr>
                                    <td>#{{ $plan->numero }}</td>
                                    <td>{{ $unidad->chasis ?: 'Unidad ' . $unidad->numero_unidad }}</td>
                                    <td>{{ $eta['etapa'] }}</td>
                                    <td>{{ $plan->fecha_fin_planificada?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $eta['original'] ? \Carbon\Carbon::parse($eta['original'])->format('d/m/Y') : '—' }}
                                    </td>
                                    <td>{{ $eta['texto'] }}</td>
                                    <td>{{ collect($eta['demoras_activas'])->pluck('motivo')->implode(' · ') ?: '—' }}</td>
                                    <td><a class="btn btn-sm btn-outline-primary text-nowrap"
                                            href="{{ route('planes.productos.unidades.show', [$plan, $linea, $unidad]) }}">Ver
                                            ficha</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
        @if ($entregasEnRiesgo->isNotEmpty())
            <details class="alert alert-danger">
                <summary class="fw-semibold">
                    Entregas en riesgo o vencidas · {{ $entregasEnRiesgo->count() }} · Ver detalle
                </summary>
                <p class="small mt-2">Ventas confirmadas con unidades pendientes de entrega cuya fecha pactada ya pasó o
                    cuya estimación supera el compromiso con el cliente.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Chasis</th>
                                <th>Cliente</th>
                                <th>Etapa</th>
                                <th>Fecha pactada</th>
                                <th>Entrega estimada</th>
                                <th>Situación</th>
                                <th>Venta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entregasEnRiesgo->sortBy(fn($fila) => $fila['venta']->fecha_entrega_pactada->toDateString()) as $fila)
                                @php
                                    $unidad = $fila['unidad'];
                                    $venta = $fila['venta'];
                                    $linea = $unidad->planProducto;
                                    $plan = $linea->plan;
                                    $eta = $unidad->estimacion_entrega;
                                @endphp
                                <tr>
                                    <td>#{{ $plan->numero }}</td>
                                    <td><a
                                            href="{{ route('planes.productos.unidades.show', [$plan, $linea, $unidad]) }}">{{ $unidad->chasis ?: 'Unidad ' . $unidad->numero_unidad }}</a>
                                    </td>
                                    <td>{{ $venta->cliente?->nombre ?? '—' }}</td>
                                    <td>{{ $eta['etapa'] }}</td>
                                    <td>{{ $venta->fecha_entrega_pactada->format('d/m/Y') }}</td>
                                    <td>{{ $eta['texto'] }}</td>
                                    <td>{{ $fila['vencida'] ? 'Fecha pactada vencida' : 'Estimación posterior a la fecha pactada' }}
                                    </td>
                                    <td><a href="{{ route('ventas.show', $venta) }}">Ver venta #{{ $venta->id }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
        <section id="ue-mensual" class="card shadow-sm mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Unidades equivalentes por etapa</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url()->current() }}#ue-mensual"
                    class="d-flex gap-2 align-items-center flex-wrap mb-3">
                    @foreach (request()->except(['mes_ue', 'page']) as $clave => $valor)
                        @if (is_scalar($valor))
                            <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                        @endif
                    @endforeach
                    <label for="mes-ue">Mes de finalización</label>
                    <input id="mes-ue" class="form-control w-auto" type="month" name="mes_ue"
                        value="{{ $mesUe }}" required>
                    <button class="btn btn-outline-primary">Ver mes</button>
                </form>
                <p class="small text-muted">Etapas completadas según su fecha de fin real, para el estado del plan
                    seleccionado. Cada barra muestra su propio total. Una unidad puede pasar por varias etapas.</p>
                @php($maxUe = $resumenUe->max('ue') ?: 1)
                @forelse ($resumenUe as $fila)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <span>{{ $fila['nombre'] }} @if ($fila['sin_ue'])
                                    <small class="text-muted">· {{ $fila['sin_ue'] }} sin UE configurada</small>
                                @endif
                            </span>
                            <strong>{{ number_format($fila['ue'], 2, ',', '.') }} UE</strong>
                        </div>
                        <div class="progress" style="height: .55rem" aria-hidden="true">
                            <div class="progress-bar" style="width: {{ (100 * $fila['ue']) / $maxUe }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="mb-0">Sin etapas completadas en el mes seleccionado.</p>
                @endforelse
                @if ($resumenUe->isNotEmpty())
                    <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center gap-2">
                        <span class="fw-semibold">Total de UE del mes</span>
                        <strong class="fs-5">{{ number_format($resumenUe->sum('ue'), 2, ',', '.') }} UE</strong>
                    </div>
                @endif
            </div>
        </section>
        <div id="resultados-tablero" class="card shadow-sm mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Planes de producción</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Plan / estado</th>
                            <th>Descripción y productos</th>
                            <th>Estado real de unidades</th>
                            <th>Ventas</th>
                            <th>Planificación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($planes as $plan)
                            <tr>
                                <td><a class="fw-bold"
                                        href="{{ route('planes.avances', $plan) }}">#{{ $plan->numero }}</a>
                                    <div class="small">{{ ucfirst($plan->estado) }}</div>
                                </td>
                                <td style="min-width:250px;max-width:440px">
                                    <div style="white-space:pre-line">{{ $plan->tablero['descripcion'] }}</div>
                                    @if ($plan->observaciones)
                                        <div class="small text-muted mt-1">
                                            @foreach ($plan->planProductos as $linea)
                                                <div>{{ $linea->producto?->nombre ?? 'Producto no disponible' }} ×
                                                    {{ number_format((float) $linea->cantidad, 0, ',', '.') }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td style="min-width:180px">
                                    @forelse ($plan->tablero['estados'] as $nombre=>$cantidad)
                                        <span class="badge bg-secondary mb-1">{{ $nombre }}:
                                        {{ $cantidad }}</span> @empty<span class="text-muted">Sin unidades ·
                                            {{ $plan->tablero['general'] }}</span>
                                    @endforelse
                                </td>
                                <td style="min-width:170px"><strong>Con venta: {{ $plan->tablero['ventas']['total'] }} /
                                        {{ $plan->tablero['unidades'] }}</strong>
                                    <div class="small text-muted">Reservadas:
                                        {{ $plan->tablero['ventas']['reservadas'] }}<br>Asignadas:
                                        {{ $plan->tablero['ventas']['asignadas'] }}</div>
                                </td>
                                <td class="small">Inicio:
                                    {{ $plan->fecha_planificada?->format('d/m/Y') ?? 'Sin definir' }}<br>Fin:
                                    {{ $plan->fecha_fin_planificada?->format('d/m/Y') ?? 'Sin definir' }}</td>
                                <td>
                                    @foreach ($plan->tablero['coincidencias'] as $unidad)
                                        <a class="btn btn-sm btn-outline-primary mb-1"
                                            href="{{ route('planes.productos.unidades.show', [$plan, $unidad->planProducto, $unidad]) }}">
                                            {{ $unidad->chasis ? 'Chasis ' . $unidad->chasis : 'Unidad ' . $unidad->numero_unidad }}
                                            @if ($unidad->serie)
                                                · Serie {{ $unidad->serie }}
                                            @endif
                                        </a>
                                    @endforeach
                                    @if ($plan->tablero['coincidencias']->isNotEmpty())
                                        <br>
                                    @endif
                                    <a href="{{ route('planes.show', $plan) }}" class="btn btn-sm btn-primary mb-1">Ver
                                        unidades</a><a href="{{ route('planes.avances', $plan) }}"
                                        class="btn btn-sm btn-outline-success">Avances del plan</a>
                                </td>
                            </tr>
                        @empty<tr>
                                <td colspan="6" class="text-center text-muted p-4">No hay planes que coincidan con los
                                    filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $planes->links('pagination::bootstrap-5') }}
        @include('components.buscador-productos', [
            'productos' => $productos,
            'ayudaBuscador' => 'Seleccioná un producto para completar el filtro.',
        ])
        <script>
            const productoTablero = document.getElementById('producto-tablero');
            const idProductoTablero = document.getElementById('producto-id-tablero');
            productoTablero.addEventListener('input', () => {
                idProductoTablero.value = '';
            });
            document.getElementById('producto-filtro-tablero').addEventListener('producto-seleccionado', ({
                detail
            }) => {
                idProductoTablero.value = detail.id;
                productoTablero.value = detail.nombre;
            });
        </script>
        @include('tablero.estilos')
        @if (config('produccion.mostrar_gantt_resumen', false))
            @include('tablero.resumen')
        @endif
        @include('tablero.etapas')
</div>@endsection
