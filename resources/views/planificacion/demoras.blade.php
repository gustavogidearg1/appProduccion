@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1>Demoras · Plan #{{ $plan->numero }}</h1><a class="btn btn-outline-secondary" href="{{ route('planes.show',$plan) }}">Volver al plan</a></div>
@include('planificacion.mensajes')
<p>Alcance del nuevo registro: <strong>{{ $unidad ? 'Chasis '.($unidad->chasis ?: $unidad->id) : 'Todo el plan' }}</strong>. Las fechas planificadas originales se conservan.</p>
<div class="card mb-4"><div class="card-body">
<form method="POST" action="{{ route('demoras.guardar',$plan) }}" class="row g-3">@csrf
<input type="hidden" name="unidad_produccion_id" value="{{ $unidad?->id }}">
<div class="col-md-4"><label class="form-label">Fecha del incidente</label><input name="fecha" type="date" class="form-control" value="{{ old('fecha',now(\App\Services\EstimadorEntrega::ZONA)->toDateString()) }}" required></div>
<div class="col-md-4"><label class="form-label">Impacto adicional en días hábiles</label><input name="dias_habiles" type="number" min="0" max="3650" value="{{ old('dias_habiles',0) }}" class="form-control" required></div>
<div class="col-12"><label class="form-label">Motivo</label><textarea name="motivo" class="form-control" required maxlength="5000">{{ old('motivo') }}</textarea></div>
<p class="small text-muted mb-0">Mientras esté activa, la demora suma este impacto a la estimación vigente. Al resolverla deja de sumarlo y permanece en el historial. Usá 0 para registrar un incidente sin impacto cuantificado.</p>
<div><button class="btn btn-warning">Registrar demora</button></div></form>
</div></div>
@forelse ($demoras as $demora)
<div class="card mb-3"><div class="card-body">
<h2 class="h5">{{ $demora->unidad_produccion_id ? 'Unidad #'.$demora->unidad_produccion_id : 'Todo el plan' }} · {{ $demora->fecha }} · {{ $demora->dias_habiles }} días hábiles</h2>
<p style="white-space: pre-line">{{ $demora->motivo }}</p><p class="small text-muted">Registró: {{ $demora->registrado_por }} · {{ $demora->created_at }}</p>
@if ($demora->resuelta_at)<p class="text-success">Resuelta por {{ $demora->resuelta_por }} · {{ $demora->resuelta_at }}</p><p>{{ $demora->resolucion }}</p>
@else<form method="POST" action="{{ route('demoras.resolver',[$plan,$demora->id]) }}" class="d-flex gap-2">@csrf<input class="form-control" name="resolucion" placeholder="Cómo se resolvió" required maxlength="5000"><button class="btn btn-outline-success">Resolver</button></form>@endif
</div></div>
@empty<p>No hay demoras registradas.</p>@endforelse
</div>@endsection
