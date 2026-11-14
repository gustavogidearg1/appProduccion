@extends('layouts.app')
@section('content')
<h1>Calendario de mantenimiento</h1>@include('mantenimiento.nav')
<form class="d-flex gap-2 mb-3"><input aria-label="Mes" type="month" class="form-control w-auto" name="mes" value="{{ $mes->format('Y-m') }}"><button class="btn btn-primary">Ver mes</button></form><p>Próximo vencimiento de cada tarea activa y compromisos de órdenes abiertas. Las tareas sin fecha se consultan en el panel preventivo.</p>
@for($dia=$mes; $dia->month===$mes->month; $dia=$dia->addDay())
@if($eventos->has($dia->toDateString()) || $ordenes->has($dia->toDateString()))<div class="card card-body mb-2"><h2 class="h5">{{ $dia->format('d/m/Y') }}</h2>@foreach($eventos->get($dia->toDateString(),collect()) as $t)<p class="mb-1"><span class="badge bg-warning text-dark">Preventivo</span> <a href="{{ route('mantenimiento.maquinas.ver',$t->maquina_id) }}">{{ $t->numero }} · {{ $t->maquina_nombre }}</a> · {{ $t->nombre }}</p>@endforeach @foreach($ordenes->get($dia->toDateString(),collect()) as $o)<p class="mb-1"><span class="badge bg-primary">OT #{{ $o->id }}</span> <a href="{{ route('mantenimiento.ordenes.ver',$o->id) }}">{{ $o->titulo }}</a> · {{ $o->responsable_nombre ?: 'Sin responsable' }}</p>@endforeach</div>@endif
@endfor
@if($eventos->isEmpty() && $ordenes->isEmpty())<div class="alert alert-info">Sin fechas programadas en este mes.</div>@endif
@endsection
