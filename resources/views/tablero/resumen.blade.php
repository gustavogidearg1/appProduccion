<div class="card shadow-sm mb-4"><div class="card-header"><h2 class="h5 mb-0">Gantt · Planificado y real</h2></div><div class="card-body">
<div class="gantt-legend mb-2"><span><i class="gantt-swatch" style="background:#0d6efd"></i>Planificado</span><span><i class="gantt-swatch" style="background:#198754"></i>Real registrado</span><span><i class="gantt-swatch" style="background:#6f42c1"></i>Estimación de disponibilidad</span><span class="text-danger">│ Hoy</span></div>
<p class="small text-muted">Abrí un plan para ver sus etapas generales y la etapa actual de cada chasis. Una fecha sola se muestra como punto. Las fechas ausentes no se completan automáticamente. La barra real resume actividad registrada y no implica que el plan haya finalizado. La estimación comercial se muestra como punto, separada de la planificación.</p>
<div class="gantt-scroll"><table class="gantt-table" style="width:{{ $ancho+290 }}px"><colgroup><col style="width:290px"><col style="width:{{ $ancho }}px"></colgroup><thead><tr><th class="gantt-label gantt-axis">Plan / detalle</th><th class="gantt-axis"><div class="gantt-months">@foreach ($meses as $mes)<span style="width:{{ $mes['dias']*26 }}px;flex:0 0 {{ $mes['dias']*26 }}px">{{ $mes['nombre'] }}</span>@endforeach</div><div class="gantt-days">@foreach ($dias as $dia)<span class="{{ $dia['fin_semana'] ? 'gantt-weekend' : '' }} {{ $dia['hoy'] ? 'bg-danger text-white' : '' }}" title="{{ $dia['fecha'] }}">{{ $dia['texto'] }}</span>@endforeach</div></th></tr></thead><tbody>
@forelse ($planes as $plan)
@php
    $fila=$gantt[$plan->id];
@endphp
<tr><td class="gantt-label"><a href="{{ route('planes.show',$plan) }}" class="fw-bold">Plan #{{ $plan->numero }}</a><div class="small">Con venta: {{ $plan->tablero['ventas']['total'] }} / {{ $plan->tablero['unidades'] }}</div><div class="gantt-subtitle">Azul: plan · Verde: real · Violeta: disponibilidad</div></td><td>@include('tablero.barras',['fila'=>$fila])</td></tr>
<tr><td colspan="2"><details class="gantt-detail"><summary>Ver etapas y chasis del plan #{{ $plan->numero }}</summary><table class="gantt-table" style="width:{{ $ancho+290 }}px"><colgroup><col style="width:290px"><col style="width:{{ $ancho }}px"></colgroup><tbody>
@foreach ($fila['detalle'] as $detalle)<tr><td class="gantt-label"><a href="{{ $detalle['url'] }}">{{ $detalle['nombre'] }}</a></td><td>@include('tablero.barras',['fila'=>$detalle])</td></tr>@endforeach
</tbody></table></details></td></tr>
@empty<tr><td colspan="2" class="p-4 text-muted">Sin planes para representar.</td></tr>@endforelse
</tbody></table></div>
<p class="small text-muted mt-2 mb-0">Tabla y Gantt muestran la misma página de planes. Los indicadores superiores resumen todos los planes del estado elegido.</p>
</div></div>
