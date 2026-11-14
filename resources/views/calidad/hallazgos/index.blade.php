@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h1>Hallazgos de calidad</h1><p>Registro y seguimiento de problemas y mejoras en planta.</p></div><a class="btn btn-primary" href="{{ route('hallazgos.create') }}">Nuevo hallazgo</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
<div class="col-md-5"><label class="form-label">Buscar por título, número, plan o chasis</label><input name="buscar" value="{{ request('buscar') }}" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Estado</label><select class="form-select" name="estado">@foreach(['pendientes'=>'Pendientes','todos'=>'Mostrar todos'] + \App\Models\HallazgoCalidad::ESTADOS as $valor=>$nombre)<option value="{{ $valor }}" @selected($estado===$valor)>{{ $nombre }}</option>@endforeach</select></div>
<div class="col-md-4"><button class="btn btn-primary">Filtrar</button> <a class="btn btn-outline-secondary" href="{{ route('hallazgos.index') }}">Limpiar</a></div>
<div class="col-md-4"><label class="form-label">Responsable</label><select name="responsable" class="form-select"><option value="">Todos</option>@foreach($usuarios as $u)<option value="{{ $u->id }}" @selected((string)request('responsable')===(string)$u->id)>{{ $u->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Fecha de carga desde</label><input type="date" name="desde" value="{{ request('desde') }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Fecha de carga hasta</label><input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control"></div>
<div class="col-12"><label class="me-3"><input type="checkbox" name="mios" value="1" @checked(request('mios'))> Asignados a mí</label><label><input type="checkbox" name="vencidos" value="1" @checked(request('vencidos'))> Compromiso vencido</label></div>
</div></form>
@include('calidad.hallazgos.graficos')
<div class="table-responsive"><table class="table table-striped bg-white"><thead><tr><th>N.º / origen</th><th>Hallazgo</th><th>Plan / chasis</th><th>Responsable</th><th>Compromiso</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($hallazgos as $h)<tr><td>{{ $h->tipo }}-{{ $h->numero }}<small class="d-block">{{ $h->origen }}</small></td><td>{{ $h->titulo }}</td><td>{{ $h->plan ? '#'.$h->plan->numero : 'General de planta' }}<small class="d-block">{{ $h->unidad ? 'Chasis '.$h->unidad->chasis : '' }}</small></td><td>{{ $h->responsable?->name ?? 'Usuario no disponible' }}</td><td>{{ $h->fecha_compromiso?->format('d/m/Y') ?? 'Sin definir' }}@if(in_array($h->estado,['abierto','en_accion']) && $h->fecha_compromiso?->lt(today()))<span class="badge bg-danger d-block">Vencido</span>@endif</td><td>{{ \App\Models\HallazgoCalidad::ESTADOS[$h->estado] }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('hallazgos.show',$h) }}">Ver</a></td></tr>
@empty<tr><td colspan="7">No hay hallazgos para estos filtros.</td></tr>@endforelse
</tbody></table></div>{{ $hallazgos->links() }}
@endsection
