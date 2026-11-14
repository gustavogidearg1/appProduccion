@extends('layouts.app')
@section('content')
<h1>Hallazgos y órdenes de mantenimiento</h1>@include('mantenimiento.nav')
@include('mantenimiento.ordenes-graficos')
<p class="alert alert-info">Trabajos pendientes de prueba y liberación: <strong>{{ $porLiberar }}</strong>. Se incluyen en el listado de abiertas.</p>
<div class="d-flex flex-wrap gap-2 mb-3">@foreach(['pendiente'=>'Pendientes','en_proceso'=>'En proceso','finalizada'=>'Finalizadas','anulada'=>'Anuladas'] as $k=>$v)<a class="btn btn-outline-secondary" href="{{ route('mantenimiento.ordenes',['estado'=>$k]) }}">{{ $v }}: {{ $conteos[$k] ?? 0 }}</a>@endforeach</div>
<form class="d-flex flex-wrap gap-2 mb-3"><input class="form-control w-auto" aria-label="Buscar orden" name="buscar" placeholder="Número o título" value="{{ request('buscar') }}"><select class="form-select w-auto" name="estado" aria-label="Estado"><option value="">Abiertas</option>@foreach(['pendiente','en_proceso','finalizada','anulada','todas'] as $e)<option value="{{ $e }}" @selected(request('estado')==$e)>{{ str_replace('_',' ',$e) }}</option>@endforeach</select><button class="btn btn-primary">Buscar</button></form>
@forelse($ordenes as $o)<a class="card card-body mb-2 text-decoration-none" href="{{ route('mantenimiento.ordenes.ver',$o->id) }}"><strong>OT #{{ $o->id }} · {{ $o->titulo }}</strong>@if($o->estado_liberacion==='pendiente')<span class="text-warning fw-bold">Pendiente de liberación</span>@endif<span>{{ str_replace('_',' ',$o->estado) }} · Prioridad {{ $o->prioridad }} · {{ $o->responsable_nombre ?: 'Sin responsable' }} · Compromiso: {{ $o->fecha_compromiso ?: 'Sin definir' }}</span>@if($o->fecha_compromiso && $o->fecha_compromiso < today()->toDateString() && in_array($o->estado,['pendiente','en_proceso']))<span class="text-danger">Compromiso vencido</span>@endif</a>@empty<p>No hay órdenes con estos filtros.</p>@endforelse
{{ $ordenes->links() }}
@endsection
