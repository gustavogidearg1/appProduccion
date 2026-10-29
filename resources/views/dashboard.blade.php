@extends('layouts.app')
@section('title', 'Tablero de producción')
@section('content')
<div class="container-fluid py-4">
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4"><div><h1 class="fw-bold">Tablero de producción</h1><p class="text-muted mb-0">Estados reales, compromiso comercial y planificación</p></div><span class="badge bg-success">Datos del sistema · {{ $hoy->format('d/m/Y') }}</span></div>
@include('planificacion.mensajes')
<form method="GET" action="{{ route('dashboard') }}" class="card mb-4"><div class="card-body row g-3 align-items-end">
@foreach (['sector_gantt','plan_gantt','situacion_gantt'] as $clave)@if (!empty($filtros[$clave]))<input type="hidden" name="{{ $clave }}" value="{{ $filtros[$clave] }}">@endif @endforeach

<div class="col-md-3"><label class="form-label" for="estado-tablero">Estado del plan</label><select class="form-select" name="estado" id="estado-tablero"><option value="">Todos</option>@foreach (['abierto'=>'Abierto','cerrado'=>'Cerrado','cancelado'=>'Cancelado'] as $valor=>$nombre)<option value="{{ $valor }}" @selected(($filtros['estado'] ?? '')===$valor)>{{ $nombre }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label" for="desde-gantt">Gantt desde</label><input type="date" name="desde" id="desde-gantt" class="form-control" value="{{ $filtros['desde'] }}" required></div>
<div class="col-md-3"><label class="form-label" for="hasta-gantt">Gantt hasta</label><input type="date" name="hasta" id="hasta-gantt" class="form-control" value="{{ $filtros['hasta'] }}" required></div>
<div class="col-md-3 d-flex gap-2"><button class="btn btn-primary">Aplicar</button><a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Limpiar</a></div>
<p class="small text-muted mb-0">El estado filtra todo el tablero. Las fechas cambian solo la ventana del Gantt; no ocultan planes ni modifican su planificación.</p>
</div></form>
<div class="row g-3 mb-3">
@foreach (['planes'=>'Planes','unidades'=>'Unidades generadas','con_venta'=>'Unidades con venta','demoras'=>'Unidades con demora activa'] as $clave=>$nombre)
<div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted">{{ $nombre }}</div><div class="h2 fw-bold mb-0">{{ $totales[$clave] }}</div>
@if ($clave==='con_venta')<div class="small text-muted">Reservadas: {{ $totales['reservadas'] }} · Asignadas: {{ $totales['asignadas'] }}</div>@endif
@if ($clave==='planes' && $totales['sin_unidades'])<div class="small text-muted">Sin unidades: {{ $totales['sin_unidades'] }}</div>@endif
</div></div></div>@endforeach
</div>
<p class="small text-muted">Cada chasis se cuenta una sola vez en su etapa actual. Hasta iniciar un avance individual, se usa la etapa general del plan. Las ventas anuladas y las asignaciones liberadas no cuentan como compromiso comercial.</p>
<div class="row g-3 mb-4">
@forelse ($tarjetas as $tarjeta)
<div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="fw-semibold">{{ $tarjeta['nombre'] }}</div><div class="h3 mb-1">{{ $tarjeta['cantidad'] }}</div><div class="small text-muted">unidades en esta etapa</div><div class="progress mt-2" style="height:5px"><div class="progress-bar bg-warning" style="width:{{ $totales['unidades'] ? round($tarjeta['cantidad']/$totales['unidades']*100) : 0 }}%"></div></div></div></div></div>
@empty<div class="col-12"><p class="text-muted">No hay unidades generadas para el estado seleccionado.</p></div>@endforelse
</div>
@if ($totales['vencidas'])<div class="alert alert-warning">{{ $totales['vencidas'] }} unidades tienen una previsión original vencida. Consultá la ficha para ver el cálculo y las demoras.</div>@endif
<div class="card shadow-sm mb-4"><div class="card-header"><h2 class="h5 mb-0">Planes de producción</h2></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Plan / estado</th><th>Descripción y productos</th><th>Estado real de unidades</th><th>Ventas</th><th>Planificación</th><th>Acciones</th></tr></thead><tbody>
@forelse ($planes as $plan)
<tr><td><strong>#{{ $plan->numero }}</strong><div class="small">{{ ucfirst($plan->estado) }}</div></td><td style="min-width:250px;max-width:440px"><div style="white-space:pre-line">{{ $plan->tablero['descripcion'] }}</div>
@if ($plan->observaciones)<div class="small text-muted mt-1">@foreach ($plan->planProductos as $linea)<div>{{ $linea->producto?->nombre ?? 'Producto no disponible' }} × {{ number_format((float)$linea->cantidad,0,',','.') }}</div>@endforeach</div>@endif
</td><td style="min-width:180px">@forelse ($plan->tablero['estados'] as $nombre=>$cantidad)<span class="badge bg-secondary mb-1">{{ $nombre }}: {{ $cantidad }}</span> @empty<span class="text-muted">Sin unidades · {{ $plan->tablero['general'] }}</span>@endforelse</td>
<td style="min-width:170px"><strong>Con venta: {{ $plan->tablero['ventas']['total'] }} / {{ $plan->tablero['unidades'] }}</strong><div class="small text-muted">Reservadas: {{ $plan->tablero['ventas']['reservadas'] }}<br>Asignadas: {{ $plan->tablero['ventas']['asignadas'] }}</div></td>
<td class="small">Inicio: {{ $plan->fecha_planificada?->format('d/m/Y') ?? 'Sin definir' }}<br>Fin: {{ $plan->fecha_fin_planificada?->format('d/m/Y') ?? 'Sin definir' }}</td>
<td><a href="{{ route('planes.show',$plan) }}" class="btn btn-sm btn-primary mb-1">Ver unidades</a><a href="{{ route('planes.avances',$plan) }}" class="btn btn-sm btn-outline-success">Avances del plan</a></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted p-4">No hay planes para el estado seleccionado.</td></tr>@endforelse
</tbody></table></div></div>
<style>
.gantt-scroll{overflow:auto;max-height:680px;border:1px solid #dee2e6;border-radius:6px}.gantt-table{border-collapse:separate;border-spacing:0;table-layout:fixed}.gantt-table th,.gantt-table td{border-bottom:1px solid #dee2e6;padding:0;vertical-align:top}.gantt-label{width:290px;min-width:290px;position:sticky;left:0;background:#fff;z-index:3;padding:10px!important}.gantt-axis{position:sticky;top:0;z-index:5;background:#f8f9fa}.gantt-months,.gantt-days{display:flex}.gantt-months span{font-size:12px;font-weight:600;border-right:1px solid #ddd;padding:6px}.gantt-days span{width:26px;flex:0 0 26px;text-align:center;font-size:10px;border-right:1px solid #eee;padding:3px 0}.gantt-timeline{position:relative;background:repeating-linear-gradient(to right,transparent 0,transparent 25px,#eee 25px,#eee 26px);min-height:96px}.gantt-track{height:28px;position:relative}.gantt-bar{position:absolute;height:16px;top:6px;border-radius:3px;min-width:7px}.gantt-bar.plan{background:#0d6efd}.gantt-bar.real{background:#198754}.gantt-bar.estimate{background:#6f42c1}.gantt-bar.point{border-radius:50%;width:12px!important;margin-left:7px}.gantt-bar.ongoing{background:repeating-linear-gradient(45deg,#198754,#198754 6px,#45a87c 6px,#45a87c 10px)}.gantt-today{position:absolute;border-left:2px solid #dc3545;top:0;bottom:0;pointer-events:none;z-index:2}.gantt-empty{font-size:10px;color:#6c757d;line-height:28px;padding-left:5px}.gantt-detail{margin:0}.gantt-detail summary{cursor:pointer;padding:10px;background:#f8f9fa}.gantt-subtitle{font-size:11px;color:#6c757d}.gantt-legend span{white-space:nowrap;margin-right:15px;font-size:13px}.gantt-swatch{display:inline-block;width:18px;height:9px;margin-right:5px}.gantt-weekend{background:#eceff1}
</style>
@if (config('produccion.mostrar_gantt_resumen', false))
@include('tablero.resumen')
@endif
@include('tablero.etapas')
{{ $planes->links('pagination::bootstrap-5') }}
</div>@endsection
