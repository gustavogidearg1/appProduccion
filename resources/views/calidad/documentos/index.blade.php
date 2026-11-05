@extends('layouts.app')
@section('content')
<div class="container-fluid py-4"><h1>Planilla de autocontrol · {{ $familia->nombre }}</h1>
@include('planificacion.mensajes')
<a class="btn btn-outline-secondary mb-3" href="{{ route('calidad.familias.controles.index',$familia) }}">Volver a controles de la familia</a>
@if (!$documento)
<form method="POST" action="{{ route('calidad.documentos.crear',$familia) }}" class="card card-body">@csrf
<label>Código documental<input name="codigo" class="form-control mb-2" required maxlength="100"></label><label>Nombre de planilla<input name="nombre" class="form-control mb-2" required maxlength="255"></label><button class="btn btn-primary">Crear planilla en borrador</button></form>
@else
<div class="card card-body mb-3"><strong>{{ $documento->codigo }} · {{ $documento->nombre }}</strong><p class="mb-0">Las revisiones vigentes y obsoletas se conservan. Los cambios se preparan en un borrador; no modifican registros de chasis anteriores.</p></div>
@if (!$revisiones->contains('estado','borrador'))<form method="POST" action="{{ route('calidad.documentos.nueva',$familia) }}">@csrf<button class="btn btn-primary mb-3">Preparar nueva revisión</button></form>@endif
<div class="table-responsive"><table class="table"><thead><tr><th>Revisión</th><th>Estado</th><th>Preparó</th><th>Aprobó</th><th>Vigente desde</th><th>Motivo</th><th></th></tr></thead><tbody>
@foreach ($revisiones as $r)<tr><td>{{ $r->revision ?: 'Sin identificar' }} <small>(borrador interno #{{ $r->numero }})</small></td><td>{{ ucfirst($r->estado) }}</td><td>{{ $r->preparado_por }}</td><td>{{ $r->aprobado_por ?: 'Sin aprobar' }}</td><td>{{ $r->vigente_desde ?: '-' }}</td><td>{{ $r->motivo }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('calidad.documentos.edit',[$familia,$r->id]) }}">{{ $r->estado==='borrador' ? 'Revisar / editar' : 'Consultar' }}</a> <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('calidad.documentos.imprimir',[$familia,$r->id]) }}">Imprimir en blanco</a></td></tr>@endforeach
</tbody></table></div>@endif
</div>@endsection
