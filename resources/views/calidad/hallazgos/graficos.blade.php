@php
$colores=['abierto'=>'#d69b17','en_accion'=>'#1678b8','por_verificar'=>'#7952b3','cerrado'=>'#198754','anulado'=>'#82909c'];
$maxEstado=max(1,$porEstado->max()??0); $maxResponsable=max(1,$porResponsable->max('total')??0);
$maxMes=max(1,collect($evolucion)->max('creados'),collect($evolucion)->max('cerrados'));
@endphp
<details class="card mb-3" open><summary class="card-header fw-bold" style="cursor:pointer">Indicadores de Calidad</summary><div class="card-body">
<p class="small text-muted">Gráficos y listado respetan los mismos filtros y la empresa del usuario. Inicialmente se muestran pendientes; elegí «Mostrar todos» para incluir cerrados y anulados. El período filtra por fecha de carga.</p>
<div class="row g-3 mb-3">@foreach(['total'=>'Hallazgos filtrados','abiertos'=>'Sin cerrar','vencidos'=>'Compromiso vencido','verificacion_vencida'=>'Verificación vencida'] as $k=>$label)<div class="col-6 col-lg-3"><div class="border rounded p-3"><div>{{ $label }}</div><strong class="fs-3">{{ $resumen[$k] }}</strong></div></div>@endforeach</div>
<div class="row g-3"><div class="col-lg-4"><div class="border rounded p-3 h-100"><h2 class="h6">Hallazgos por estado</h2>
@foreach(\App\Models\HallazgoCalidad::ESTADOS as $k=>$label)<div class="d-flex justify-content-between mt-3"><span>{{ $label }}</span><strong>{{ $porEstado[$k]??0 }}</strong></div><div class="progress" style="height:12px"><div class="progress-bar" style="background:{{ $colores[$k] }};width:{{ ($porEstado[$k]??0)*100/$maxEstado }}%"></div></div>@endforeach
</div></div>
<div class="col-lg-4"><div class="border rounded p-3 h-100"><h2 class="h6">Hallazgos por responsable</h2><p class="small text-muted">Cantidad asignada según los filtros. No mide el desempeño individual.</p><div style="max-height:340px;overflow-y:auto">
@forelse($porResponsable as $r)<div class="d-flex justify-content-between gap-2 mt-3"><span>{{ $usuarios->firstWhere('id',$r->responsable_id)?->name ?? 'Usuario no disponible' }}</span><strong>{{ $r->total }}</strong></div><div class="progress" style="height:12px"><div class="progress-bar bg-primary" style="width:{{ $r->total*100/$maxResponsable }}%"></div></div>@empty<p>Sin datos para estos filtros.</p>@endforelse
</div></div></div>
<div class="col-lg-4"><div class="border rounded p-3 h-100"><h2 class="h6">Evolución mensual</h2><p class="small text-muted">Seis meses hasta la fecha final seleccionada o el mes actual.</p>
@foreach($evolucion as $fila)<div class="small mt-3">{{ $fila['mes'] }} · Cargados: <strong>{{ $fila['creados'] }}</strong> · Cerrados: <strong>{{ $fila['cerrados'] }}</strong></div><div class="progress mb-1" style="height:8px"><div class="progress-bar bg-primary" style="width:{{ $fila['creados']*100/$maxMes }}%"></div></div><div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width:{{ $fila['cerrados']*100/$maxMes }}%"></div></div>@endforeach
<p class="small text-muted mt-3 mb-0">Azul: cargas. Verde: cierres con fecha de verificación registrada. Los cierres históricos sin esa fecha no se inventan. Se muestran cierres de los hallazgos que cumplen los filtros de carga; no es una comparación de desempeño.</p>
</div></div></div></div></details>
