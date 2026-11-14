@php
$estadosGrafico=['pendiente'=>['Pendientes','#d69b17'],'en_proceso'=>['En proceso','#1678b8'],'finalizada'=>['Finalizadas','#198754'],'anulada'=>['Anuladas','#82909c']];
$totalGrafico=$conteos->sum(); $tramos=[]; $acumulado=0;
foreach($estadosGrafico as $clave=>$datos){$inicio=$acumulado;$acumulado+=$totalGrafico ? ($conteos[$clave] ?? 0)*100/$totalGrafico : 0;$tramos[]=$datos[1].' '.$inicio.'% '.$acumulado.'%';}
$maxMes=max(1,collect($evolucion)->max('creadas'),collect($evolucion)->max('finalizadas'));
$maxPrioridad=max(1,$prioridades->max() ?? 0);
@endphp
<details class="card mb-3" open>
<summary class="card-header fw-bold" style="cursor:pointer">Resumen de hallazgos de mantenimiento</summary>
<div class="card-body">
<p class="small text-muted">Datos de tu empresa. Los gráficos resumen todas las órdenes; el buscador y el filtro de estado se aplican al listado de abajo.</p>
<div class="row g-3">
<div class="col-lg-4"><div class="border rounded p-3 h-100"><h2 class="h6">Hallazgos por estado</h2>
<div class="d-flex align-items-center flex-wrap gap-3">
<div role="img" aria-label="Total de hallazgos: {{ $totalGrafico }}" style="width:140px;height:140px;border-radius:50%;background:{{ $totalGrafico ? 'conic-gradient('.implode(',',$tramos).')' : '#e9ecef' }};display:grid;place-items:center;flex-shrink:0"><div class="bg-white rounded-circle text-center" style="width:104px;height:104px;display:grid;place-content:center"><strong class="fs-3">{{ $totalGrafico }}</strong><small>Total</small></div></div>
<div>@foreach($estadosGrafico as $clave=>$datos)<div class="mb-2"><span aria-hidden="true" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $datos[1] }}"></span> {{ $datos[0] }}: <strong>{{ $conteos[$clave] ?? 0 }}</strong></div>@endforeach</div>
</div></div></div>
<div class="col-lg-4"><div class="border rounded p-3 h-100"><h2 class="h6">Prioridad de los hallazgos abiertos</h2>
@foreach(['alta'=>['Alta','#dc3545'],'media'=>['Media','#d69b17'],'baja'=>['Baja','#198754']] as $clave=>$datos)
<div class="d-flex justify-content-between mt-3"><span>{{ $datos[0] }}</span><strong>{{ $prioridades[$clave] ?? 0 }}</strong></div><div class="progress" style="height:12px"><div class="progress-bar" style="background:{{ $datos[1] }};width:{{ ($prioridades[$clave] ?? 0)*100/$maxPrioridad }}%"></div></div>
@endforeach
<p class="mt-3 mb-0 {{ $vencidas ? 'text-danger' : 'text-muted' }}"><strong>{{ $vencidas }}</strong> con compromiso vencido</p><small class="text-muted">Pendientes y en proceso. Finalizadas y anuladas quedan excluidas.</small></div></div>
<div class="col-lg-4"><div class="border rounded p-3 h-100"><h2 class="h6">Evolución mensual</h2><p class="small text-muted">Últimos seis meses · cantidades de hallazgos</p>
<div class="small mb-2"><span class="text-primary">■ Creados</span> · <span class="text-success">■ Finalizados</span></div>
@foreach($evolucion as $fila)
<div class="small mt-2">{{ $fila['mes'] }} · Creados: <strong>{{ $fila['creadas'] }}</strong> · Finalizados: <strong>{{ $fila['finalizadas'] }}</strong></div>
<div class="progress mb-1" style="height:7px"><div class="progress-bar bg-primary" style="width:{{ $fila['creadas']*100/$maxMes }}%"></div></div><div class="progress" style="height:7px"><div class="progress-bar bg-success" style="width:{{ $fila['finalizadas']*100/$maxMes }}%"></div></div>
@endforeach
<small class="text-muted d-block mt-2">Creados según fecha de carga; finalizados según fecha real de cierre. No necesariamente corresponden a los mismos hallazgos.</small></div></div>
</div></div></details>
