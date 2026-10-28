@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1>Corregir avance · {{ $etapa->nombre }}</h1><a class="btn btn-outline-secondary" href="{{ route('planes.productos.unidades.avances',[$plan,$planProducto,$unidad]) }}">Volver a avances</a></div>
<p>Plan #{{ $plan->numero }} · Chasis {{ $unidad->chasis ?: $unidad->numero_unidad }} · {{ $planProducto->producto?->nombre }}</p>
@include('planificacion.mensajes')
@if ($bloqueada)
<div class="alert alert-warning">Esta unidad tiene un control de entrega registrado. Sus avances no se pueden corregir desde aquí.</div>
<a class="btn btn-outline-success" href="{{ route('entregas.index',$unidad) }}">Ver control de entrega</a>
@else
<p class="text-muted">Las fechas planificadas no se modifican. Cada corrección conserva los valores anteriores, el responsable y el motivo.</p>
@if ($etapa->codigo === 'ENTREGADO')<div class="alert alert-info">Para corregir una entrega anterior de prueba, dejá esta etapa pendiente y borrá sus fechas reales. Una entrega nueva se registra desde Control de entrega y fotos.</div>@endif
<form method="POST" action="{{ route('avances.correccion.update',[$plan,$planProducto,$unidad,$etapa]) }}" class="card"><div class="card-body">@csrf @method('PUT')
<input type="hidden" name="version" value="{{ $version }}">
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="estado" required>
@foreach (['pendiente'=>'Pendiente','en_proceso'=>'En proceso','completada'=>'Completada'] as $valor=>$texto)<option value="{{ $valor }}" @selected(old('estado',$avance?->estado ?? 'pendiente')===$valor)>{{ $texto }}</option>@endforeach
</select></div>
<div class="col-md-4"><label class="form-label">Inicio real</label><input type="datetime-local" step="1" class="form-control" name="fecha_inicio" value="{{ old('fecha_inicio',$avance?->fecha_inicio?->format('Y-m-d\TH:i:s')) }}"></div>
<div class="col-md-4"><label class="form-label">Finalización real</label><input type="datetime-local" step="1" class="form-control" name="fecha_fin" value="{{ old('fecha_fin',$avance?->fecha_fin?->format('Y-m-d\TH:i:s')) }}"></div>
<div class="col-12 small text-muted">Para Pendiente, borrá ambas fechas. Para En proceso, indicá solo el inicio. Para Completada, indicá ambas fechas. Se usa el mismo horario mostrado en Avances.</div>
<div class="col-12"><label class="form-label">Observaciones del avance</label><textarea name="observaciones" class="form-control" maxlength="5000">{{ old('observaciones',$avance?->observaciones) }}</textarea></div>
<div class="col-12"><label class="form-label">Motivo de la corrección</label><textarea name="motivo" class="form-control" maxlength="5000" required>{{ old('motivo') }}</textarea></div>
@if ($posteriores->isNotEmpty())
<div class="col-12 alert alert-warning">Etapas posteriores con avances: {{ $posteriores->pluck('etapa.nombre')->join(', ') }}.
<label class="d-block mt-2"><input type="checkbox" name="reiniciar_posteriores" value="1" @checked(old('reiniciar_posteriores'))> Si cambio esta etapa a pendiente o en proceso, confirmo devolver estas etapas posteriores a pendiente y quitar sus fechas reales. Sus datos anteriores se conservarán en el historial.</label></div>
@endif
<div><button class="btn btn-warning">Guardar corrección</button></div>
</div></div></form>
@endif
</div>@endsection
