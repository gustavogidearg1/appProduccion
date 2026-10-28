@extends('layouts.app')
@section('content')
<div class="container-fluid py-4"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1>Administrar plan #{{ $plan->numero }}</h1><a class="btn btn-outline-secondary" href="{{ route('planes.index',['estado'=>'']) }}">Volver a planes</a></div>
@include('planificacion.mensajes')
<div class="card mb-4"><div class="card-body"><p>Estado: <strong>{{ $plan->estado === 'cancelado' ? 'Anulado' : ucfirst($plan->estado) }}</strong> · Unidades: {{ $cantidad }} · Reservadas/asignadas: {{ $reservadas }} · Entregadas: {{ $entregadas }}</p>
@foreach ($plan->planProductos as $linea)<div>{{ $linea->producto?->nombre ?? 'Producto no disponible' }} × {{ $linea->cantidad }}</div>@endforeach
<p class="mt-3 mb-0">La anulación conserva productos, chasis, avances, controles y fotos. El plan deja de aparecer entre los abiertos y sus unidades no se ofrecen para vender. Al reactivarlo vuelve a Abierto y conserva esos datos.</p></div></div>
@if ($plan->estado!=='cancelado' && ($reservadas || $entregadas || $controles))
<div class="alert alert-warning">Este plan no puede anularse mientras tenga reservas/asignaciones activas o entregas registradas. Si son pruebas, corregí primero esas entregas o anulá las ventas correspondientes.</div>
@else
<form method="POST" action="{{ route('planes.estado.update',$plan) }}" class="card mb-4"><div class="card-body">@csrf @method('PUT')
<input type="hidden" name="accion" value="{{ $plan->estado==='cancelado' ? 'reactivar' : 'anular' }}">
<label class="form-label">Motivo</label><textarea name="motivo" class="form-control mb-3" maxlength="5000" required>{{ old('motivo') }}</textarea>
<label class="d-block mb-3"><input type="checkbox" name="confirmacion" value="1" required> Confirmo que quiero {{ $plan->estado==='cancelado' ? 'reactivar' : 'anular' }} este plan, conservando sus registros.</label>
<button class="btn {{ $plan->estado==='cancelado' ? 'btn-success' : 'btn-danger' }}">{{ $plan->estado==='cancelado' ? 'Reactivar plan' : 'Anular plan' }}</button>
</div></form>@endif
<div class="card"><div class="card-header">Historial del plan</div><div class="card-body">
@forelse ($historial as $h)<div class="border-bottom py-2"><strong>{{ $h->accion==='anular' ? 'Anulación' : 'Reactivación' }}</strong> · {{ $h->created_at }} · {{ $h->responsable }}<div>{{ $h->estado_anterior }} → {{ $h->estado_nuevo }}</div><p class="mb-0" style="white-space:pre-line">{{ $h->motivo }}</p></div>@empty<p class="text-muted">Sin cambios de estado registrados.</p>@endforelse
{{ $historial->links('pagination::bootstrap-5') }}</div></div></div>
@endsection
