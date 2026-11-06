@php($vistaGantt = isset($escala))
<style>#gantt-etapas .gantt-label{padding:6px 10px!important}#gantt-etapas .gantt-timeline{min-height:56px}</style>
<div class="card shadow-sm mb-4" id="gantt-etapas">
<div class="card-header"><h2 class="h5 mb-0">Gantt por sector · Comparar planes</h2></div>
<div class="card-body">
<form method="GET" action="{{ route($vistaGantt ? 'gantt.sectores' : 'dashboard') }}#gantt-etapas" class="row g-3 align-items-end mb-3">
@if ($vistaGantt)
<div class="col-md-4"><label class="form-label" for="estado-gantt">Estado del plan</label><select class="form-select" name="estado" id="estado-gantt"><option value="">Todos</option>@foreach (['abierto'=>'Abierto','cerrado'=>'Cerrado','cancelado'=>'Cancelado'] as $valor=>$nombre)<option value="{{ $valor }}" @selected(($filtros['estado'] ?? '')===$valor)>{{ $nombre }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="escala-gantt">Escala</label><select class="form-select" name="escala" id="escala-gantt">@foreach (['semana'=>'Semana','mes'=>'Mes','anio'=>'Año'] as $valor=>$nombre)<option value="{{ $valor }}" @selected($escala===$valor)>{{ $nombre }}</option>@endforeach</select></div>
@else
<input type="hidden" name="estado" value="{{ $filtros['estado'] ?? '' }}">
@endif
<div class="col-md-4"><label class="form-label" for="sector-gantt">Sector</label><select class="form-select" name="sector_gantt" id="sector-gantt"><option value="">Todos los sectores</option>@foreach ($sectoresGantt as $sector)<option value="{{ $sector->codigo }}" @selected(($filtros['sector_gantt'] ?? '')===$sector->codigo)>{{ $sector->nombre }}</option>@endforeach</select></div>
<div class="col-md-4"><x-buscador-planes id="plan-gantt" name="plan_gantt" :planes="$planesGantt" :seleccion="$filtros['plan_gantt'] ?? null" /></div>
<div class="col-md-4"><label class="form-label" for="situacion-gantt">Situación del sector</label><select class="form-select" name="situacion_gantt" id="situacion-gantt"><option value="">Todas</option>@foreach (['pendiente'=>'Pendiente','en_proceso'=>'En proceso','completada'=>'Completada','sin_planificar'=>'Planificación faltante o a revisar','coincidencias'=>'Con coincidencias entre planes'] as $valor=>$nombre)<option value="{{ $valor }}" @selected(($filtros['situacion_gantt'] ?? '')===$valor)>{{ $nombre }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="desde-sector">Desde</label><input type="date" class="form-control" id="desde-sector" name="desde" value="{{ $filtros['desde'] }}" required></div>
<div class="col-md-4"><label class="form-label" for="hasta-sector">Hasta</label><input type="date" class="form-control" id="hasta-sector" name="hasta" value="{{ $filtros['hasta'] }}" required></div>
<div class="col-md-4 d-flex gap-2"><button class="btn btn-primary">Filtrar Gantt</button><a class="btn btn-outline-secondary" href="{{ $vistaGantt ? route('gantt.sectores') : route('dashboard',array_intersect_key($filtros,array_flip(['estado','desde','hasta']))) }}#gantt-etapas">Limpiar filtros</a></div>
</form>
@if (!$vistaGantt)
<p class="small text-muted">Sector, plan y situación filtran solo este Gantt. Las fechas comparten el período elegido arriba. Las coincidencias se comparan con todos los planes del estado seleccionado, incluso al elegir un solo plan.</p>

<p class="small text-muted">Cada sector agrupa todos los planes del estado elegido, aunque estén en otra página. Usa las fechas de «Avances del plan». Azul: planificado; verde: real registrado. Los avances individuales se consultan desde la ficha del chasis.</p>
@else
<p class="small text-muted">Azul indica lo planificado y verde lo registrado. Las coincidencias se comparan con todos los planes del estado elegido, incluso al filtrar un solo plan.</p>
@endif
<p class="small text-muted">«Coincide con» indica planes que ocupan el mismo sector al menos un día del período visible. Es una señal para revisar la carga: no significa que la capacidad esté excedida. Los planes anulados no cuentan como ocupación.</p>
<div class="gantt-scroll"><table class="gantt-table" style="width:{{ $ancho+290 }}px">
<colgroup><col style="width:290px"><col style="width:{{ $ancho }}px"></colgroup>
<thead><tr><th class="gantt-label gantt-axis">Sector / plan</th><th class="gantt-axis">
@if ($vistaGantt)
<div class="gantt-months">@foreach ($columnas as $columna)<span class="text-truncate" style="flex:0 0 {{ $columna['ancho'] }}px;min-width:0" title="{{ $columna['desde'] }} al {{ $columna['hasta'] }}">{{ $columna['nombre'] }}</span>@endforeach</div>
@else
<div class="gantt-months">@foreach ($meses as $mes)<span style="width:{{ $mes['dias']*26 }}px;flex:0 0 {{ $mes['dias']*26 }}px">{{ $mes['nombre'] }}</span>@endforeach</div>
<div class="gantt-days">@foreach ($dias as $dia)<span class="{{ $dia['fin_semana'] ? 'gantt-weekend' : '' }} {{ $dia['hoy'] ? 'bg-danger text-white' : '' }}" title="{{ $dia['fecha'] }}">{{ $dia['texto'] }}</span>@endforeach</div>
@endif
</th></tr></thead><tbody>
@forelse ($ganttEtapas as $grupo)
<tr><th class="gantt-label bg-light">{{ $grupo['nombre'] }}</th><td class="bg-light"><span class="small text-muted px-2">{{ count($grupo['filas']) }} planes</span></td></tr>
@forelse ($grupo['filas'] as $filaEtapa)
<tr><td class="gantt-label">
<a class="fw-bold" href="{{ route('planes.avances',$filaEtapa['plan']) }}">Plan #{{ $filaEtapa['plan']->numero }}</a>
<div class="gantt-subtitle">{{ $filaEtapa['plan']->planProductos->map(fn ($linea) => ($linea->producto?->nombre ?? 'Producto no disponible').' × '.number_format((float) $linea->cantidad, 0, ',', '.'))->implode(' · ') }}</div>
@if ($filaEtapa['aviso'])<div class="small text-warning-emphasis">{{ $filaEtapa['aviso'] }}</div>@endif
@if ($filaEtapa['coincidencias'])<div class="small text-warning-emphasis">Coincide con: {{ collect($filaEtapa['coincidencias'])->map(fn($n)=>'#'.$n)->implode(', ') }}</div>@endif
</td><td>@include('tablero.barras',['fila'=>$filaEtapa])</td></tr>
@empty<tr><td class="gantt-label text-muted">Sin planes</td><td></td></tr>@endforelse
@empty<tr><td colspan="2" class="p-3 text-muted">No hay resultados para los filtros elegidos.</td></tr>@endforelse
</tbody></table></div>
<p class="small text-muted mt-2 mb-0">Sin fechas: sin planificar. Con una sola fecha: punto, sin duración supuesta. Abrí el número de plan para corregir sus fechas.</p>
</div></div>
