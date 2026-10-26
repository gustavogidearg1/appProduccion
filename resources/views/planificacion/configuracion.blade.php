@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
<h1>Plazos de entrega y calendario</h1>
<p class="text-muted">Días hábiles restantes para entregar desde cada etapa. No son duraciones para sumar entre sí. Sábados y domingos no se cuentan.</p>
@include('planificacion.mensajes')
<div class="card mb-4"><div class="card-header">Plazos por producto</div><div class="card-body">
<form method="GET" action="{{ route('plazos.index') }}" class="d-flex gap-2 mb-3">
<select name="producto_id" class="form-select" required><option value="">Seleccionar producto</option>
@foreach ($productos as $producto)<option value="{{ $producto->id }}" @selected($productoId == $producto->id)>{{ $producto->nombre }}</option>@endforeach
</select><button class="btn btn-primary">Ver plazos</button></form>
@if ($productoId)
<form method="POST" action="{{ route('plazos.guardar') }}">@csrf
<input type="hidden" name="producto_id" value="{{ $productoId }}">
<p class="small text-muted">Vacío = plazo sin definir. «Desde hoy» mantiene el plazo comercial vigente aunque la etapa se haya alcanzado hace meses. OK Calidad completada ofrece entrega inmediata, salvo demoras activas.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Etapa</th><th>Alcance</th><th>Días hábiles restantes</th><th>Calcular siempre desde hoy</th></tr></thead><tbody>
@foreach ($etapas as $etapa)
@php
    $regla = $reglas->get($etapa->id);
@endphp
<tr><td>{{ $etapa->nombre }}</td><td>{{ ucfirst($etapa->alcance) }}</td><td><input aria-label="Días para {{ $etapa->nombre }}" type="number" class="form-control" name="plazos[{{ $etapa->id }}][dias]" min="0" max="3650" value="{{ old('plazos.'.$etapa->id.'.dias', $regla?->activo ? $regla->dias_habiles : '') }}"></td>
<td><input type="hidden" name="plazos[{{ $etapa->id }}][desde_hoy]" value="0"><input aria-label="Desde hoy en {{ $etapa->nombre }}" type="checkbox" name="plazos[{{ $etapa->id }}][desde_hoy]" value="1" @checked(old('plazos.'.$etapa->id.'.desde_hoy', $regla?->desde_hoy))></td></tr>
@endforeach
</tbody></table></div><button class="btn btn-success">Guardar plazos</button></form>
@endif
</div></div>
<div class="card mb-4"><div class="card-header">Calendario no laboral</div><div class="card-body">
<p>Estos períodos se aplican a toda la fábrica. Para una demora de un plan o chasis, usá «Demoras» dentro del plan.</p>
<form method="POST" action="{{ route('plazos.calendario') }}" class="row g-3">@csrf
<div class="col-md-3"><label class="form-label">Desde</label><input type="date" name="desde" class="form-control" value="{{ old('desde') }}" required></div>
<div class="col-md-3"><label class="form-label">Hasta, inclusive</label><input type="date" name="hasta" class="form-control" value="{{ old('hasta') }}" required></div>
<div class="col-md-3"><label class="form-label">Tipo</label><select name="tipo" class="form-select"><option value="feriado">Feriado</option><option value="no_laboral">No laboral</option><option value="vacaciones">Vacaciones</option></select></div>
<div class="col-md-3"><label class="form-label">Motivo</label><input name="motivo" class="form-control" maxlength="255" value="{{ old('motivo') }}" required></div>
<div><button class="btn btn-primary">Agregar período</button></div></form>
<div class="table-responsive mt-3"><table class="table"><thead><tr><th>Desde</th><th>Hasta</th><th>Tipo / motivo</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
@forelse ($cierres as $cierre)<tr><td>{{ \Carbon\Carbon::parse($cierre->desde)->format('d/m/Y') }}</td><td>{{ \Carbon\Carbon::parse($cierre->hasta)->format('d/m/Y') }}</td><td>{{ ucfirst(str_replace('_',' ',$cierre->tipo)) }} · {{ $cierre->motivo }}</td><td>{{ $cierre->activo ? 'Activo' : 'Anulado' }}</td><td><form method="POST" action="{{ route('plazos.calendario.alternar',$cierre->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $cierre->activo ? 'Anular' : 'Reactivar' }}</button></form></td></tr>
@empty<tr><td colspan="5">No hay períodos registrados.</td></tr>@endforelse
</tbody></table></div>{{ $cierres->links('pagination::bootstrap-5') }}
</div></div></div>
@endsection
