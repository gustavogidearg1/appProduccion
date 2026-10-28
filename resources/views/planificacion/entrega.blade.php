@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1>Control de entrega · Chasis {{ $unidad->chasis ?: $unidad->id }}</h1><a class="btn btn-outline-secondary" href="{{ route('planes.productos.unidades.show',[$unidad->planProducto->plan,$unidad->planProducto,$unidad]) }}">Volver a ficha</a></div>
@include('planificacion.mensajes')
<p>{{ $unidad->planProducto->producto?->nombre }} · {{ $asignacion?->detalle?->venta?->cliente?->nombre ?? 'Sin cliente de venta activa' }}</p>
@if ($asignacion)
<div class="card mb-3"><div class="card-header">Observaciones de la venta #{{ $asignacion->detalle->venta->id }}</div>
<div class="card-body" style="white-space: pre-line">{{ $asignacion->detalle->venta->observaciones ?: 'Sin observaciones de venta.' }}</div></div>
@endif
@if ($entrega)
<div class="card mb-3"><div class="card-body"><h2 class="h5">Entrega registrada · {{ $entrega->fecha }}</h2><p>Recibió: {{ $entrega->recibido_por }} · Responsable: {{ $entrega->responsable }}</p><p>Identificación, documentación y accesorios verificados.</p><p style="white-space: pre-line">{{ $entrega->observaciones ?: '-' }}</p></div></div>
<div class="row g-3 mb-4">@foreach ($fotos as $foto)<div class="col-6 col-md-3"><a href="{{ route('entregas.foto',[$unidad,$foto->id]) }}" target="_blank" rel="noopener"><img src="{{ route('entregas.foto',[$unidad,$foto->id]) }}" class="img-fluid rounded" alt="Evidencia de entrega {{ $loop->iteration }}"></a></div>@endforeach</div>
<form method="POST" enctype="multipart/form-data" action="{{ route('entregas.fotos',$unidad) }}">@csrf<label class="form-label">Agregar fotos (hasta 20 en total)</label><input name="fotos[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="form-control mb-2" required><button class="btn btn-primary">Guardar fotos</button></form>
@elseif ($entregaAnterior)
<div class="alert alert-warning">
    Esta unidad figura entregada en los avances anteriores, pero no tiene un control de salida registrado.
    El campo de fecha del formulario registra una entrega nueva; no permite borrar esta entrega anterior.
    @if ($avanceEntrega?->fecha_fin)<div>Finalización anterior: {{ $avanceEntrega->fecha_fin->timezone(\App\Services\EstimadorEntrega::ZONA)->format('d/m/Y H:i') }}.</div>@endif
</div>
<div class="card"><div class="card-header">Corregir una entrega anterior de prueba</div><div class="card-body">
<p>Usá esta opción únicamente si no hubo una entrega real. La etapa Entregado volverá a pendiente, se quitarán sus fechas reales y el estado se recalculará según los avances anteriores. La reserva y los datos anteriores se conservarán en el historial de la venta.</p>
<form method="POST" action="{{ route('entregas.corregir-anterior', $unidad) }}">@csrf
<label for="motivo-correccion" class="form-label">Motivo de la corrección</label>
<textarea id="motivo-correccion" name="motivo" class="form-control mb-3" required maxlength="5000">{{ old('motivo') }}</textarea>
<label class="d-block mb-3"><input type="checkbox" name="confirmacion" value="1" required> Confirmo que fue una prueba y que esta unidad no se entregó realmente.</label>
<button class="btn btn-warning" @disabled(!$asignacion)>Corregir entrega de prueba</button>
</form></div></div>
@else
@if ($pendientes->isNotEmpty())<div class="alert alert-warning">Etapas pendientes: {{ $pendientes->pluck('nombre')->join(', ') }}. Deben completarse antes de entregar.</div>@endif
<p class="text-muted">La entrega requiere venta confirmada y etapas anteriores completas. Las observaciones de calidad quedan registradas y no bloquean la salida una vez completados los controles.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('entregas.guardar',$unidad) }}" class="card"><div class="card-body">@csrf
<div class="row g-3"><div class="col-md-6"><label class="form-label">Fecha de entrega</label><input name="fecha" type="date" value="{{ old('fecha',now(\App\Services\EstimadorEntrega::ZONA)->toDateString()) }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Recibido por</label><input name="recibido_por" class="form-control" value="{{ old('recibido_por') }}" maxlength="255" required></div>
@foreach (['identificacion_ok'=>'Chasis e identificación verificados','documentacion_ok'=>'Documentación de entrega verificada','accesorios_ok'=>'Componentes y accesorios del pedido verificados'] as $campo=>$texto)
<div class="col-12"><label><input type="checkbox" name="{{ $campo }}" value="1" @checked(old($campo)) required> {{ $texto }}</label></div>@endforeach
<div class="col-12"><label class="form-label">Observaciones de esta entrega</label><textarea name="observaciones" class="form-control" maxlength="5000">{{ old('observaciones') }}</textarea></div>
<div class="col-12"><label class="form-label">Fotos opcionales · JPG, PNG o WEBP · hasta 10 de 5 MB cada una</label><input type="file" name="fotos[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control"></div>
<div><button class="btn btn-success" @disabled($pendientes->isNotEmpty() || !$asignacion)>Confirmar entrega</button></div></div>
</div></form>@endif
</div>@endsection
