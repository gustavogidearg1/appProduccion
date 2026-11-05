@extends('layouts.app')
@section('content')
<div class="container-fluid py-4"><div class="d-flex justify-content-between flex-wrap gap-2"><div><h1>Controles de calidad</h1><p>Familia: {{ $familia->nombre }}</p></div><div><a class="btn btn-primary" href="{{ route('calidad.configuracion.nuevo',$familia) }}">Nuevo control</a> <a class="btn btn-outline-secondary" href="{{ route('calidad.secciones',['familia_id'=>$familia->id]) }}">Secciones y orden</a></div></div>
@include('planificacion.mensajes')
<div class="alert alert-info">{{ $e['r']?->estado==='borrador' ? 'Estás trabajando en la preparación de esta familia.' : 'Configuración vigente. Al guardar el primer cambio se preparará una copia de trabajo.' }} Podés guardar tantos cambios como necesites; la revisión y el motivo se completan una sola vez al aprobar el conjunto. Los registros de planta anteriores se conservan.
@if($e['doc'])<a class="ms-2" href="{{ route('calidad.documentos.index',$familia) }}">Revisiones y aprobación</a>@endif
@if($e['r'])<a class="ms-2" target="_blank" href="{{ route('calidad.documentos.imprimir',[$familia,$e['r']->id]) }}">Vista de impresión</a>@endif</div>
<form method="GET" class="mb-3"><label><input type="checkbox" name="inactivos" value="1" @checked(request()->boolean('inactivos')) onchange="this.form.submit()"> Mostrar también controles inactivos</label></form>
<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Sección / orden</th><th>Orden del punto</th><th>Punto a controlar</th><th>Etapa</th><th>Obligatorio</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($items as $i)<tr><td>{{ $i['seccion'] }} <small class="d-block text-muted">Orden {{ $i['seccion_orden']??'-' }}</small></td><td>{{ $i['orden']??$i['indice']+1 }}</td><td>{{ $i['nombre'] }}</td><td>{{ $etapas[$i['etapa_id']]??'-' }}</td><td>{{ !empty($i['obligatorio'])?'Sí':'No' }}</td><td><span class="badge {{ ($i['activo']??true)?'bg-success':'bg-secondary' }}">{{ ($i['activo']??true)?'Activo':'Inactivo' }}</span>@if(!($i['seccion_activa']??true))<small class="d-block">Sección inactiva: no se imprimirá</small>@endif</td><td><a class="btn btn-sm btn-warning" href="{{ route('calidad.configuracion.editar',[$familia,$i['indice']]) }}">Editar</a></td></tr>
@empty<tr><td colspan="7">No hay controles para mostrar.</td></tr>@endforelse</tbody></table></div></div>
@endsection
