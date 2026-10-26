@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1>Control de entrega · Chasis {{ $unidad->chasis ?: $unidad->id }}</h1><a class="btn btn-outline-secondary" href="{{ route('planes.productos.unidades.show',[$unidad->planProducto->plan,$unidad->planProducto,$unidad]) }}">Volver a ficha</a></div>
@include('planificacion.mensajes')
<p>{{ $unidad->planProducto->producto?->nombre }} · {{ $asignacion?->detalle?->venta?->cliente?->nombre ?? 'Sin cliente de venta activa' }}</p>
@if ($entrega)
<div class="card mb-3"><div class="card-body"><h2 class="h5">Entrega registrada · {{ $entrega->fecha }}</h2><p>Recibió: {{ $entrega->recibido_por }} · Responsable: {{ $entrega->responsable }}</p><p>Identificación, documentación y accesorios verificados.</p><p style="white-space: pre-line">{{ $entrega->observaciones ?: '-' }}</p></div></div>
<div class="row g-3 mb-4">@foreach ($fotos as $foto)<div class="col-6 col-md-3"><a href="{{ route('entregas.foto',[$unidad,$foto->id]) }}" target="_blank" rel="noopener"><img src="{{ route('entregas.foto',[$unidad,$foto->id]) }}" class="img-fluid rounded" alt="Evidencia de entrega {{ $loop->iteration }}"></a></div>@endforeach</div>
<form method="POST" enctype="multipart/form-data" action="{{ route('entregas.fotos',$unidad) }}">@csrf<label class="form-label">Agregar fotos (hasta 20 en total)</label><input name="fotos[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="form-control mb-2" required><button class="btn btn-primary">Guardar fotos</button></form>
@else
@if ($pendientes->isNotEmpty())<div class="alert alert-warning">Etapas pendientes: {{ $pendientes->pluck('nombre')->join(', ') }}. Deben completarse antes de entregar.</div>@endif
<p class="text-muted">La entrega requiere venta confirmada y etapas anteriores completas. Las observaciones de calidad quedan registradas y no bloquean la salida una vez completados los controles.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('entregas.guardar',$unidad) }}" class="card"><div class="card-body">@csrf
<div class="row g-3"><div class="col-md-6"><label class="form-label">Fecha de entrega</label><input name="fecha" type="date" value="{{ old('fecha',now(\App\Services\EstimadorEntrega::ZONA)->toDateString()) }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Recibido por</label><input name="recibido_por" class="form-control" value="{{ old('recibido_por') }}" maxlength="255" required></div>
@foreach (['identificacion_ok'=>'Chasis e identificación verificados','documentacion_ok'=>'Documentación de entrega verificada','accesorios_ok'=>'Componentes y accesorios del pedido verificados'] as $campo=>$texto)
<div class="col-12"><label><input type="checkbox" name="{{ $campo }}" value="1" @checked(old($campo)) required> {{ $texto }}</label></div>@endforeach
<div class="col-12"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" maxlength="5000">{{ old('observaciones') }}</textarea></div>
<div class="col-12"><label class="form-label">Fotos opcionales · JPG, PNG o WEBP · hasta 10 de 5 MB cada una</label><input type="file" name="fotos[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control"></div>
<div><button class="btn btn-success" @disabled($pendientes->isNotEmpty() || !$asignacion)>Confirmar entrega</button></div></div>
</div></form>@endif
</div>@endsection
