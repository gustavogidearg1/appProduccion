@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1>Gantt por sector</h1>
    <p class="text-muted">Compará la planificación y el avance real de los planes por semana, mes o año.</p>
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @include('tablero.estilos')
    <style>
        #gantt-etapas .gantt-timeline { background: none; }
        #gantt-etapas .gantt-bar.point { margin-left: 0; }
    </style>
    @include('tablero.etapas')
</div>
@endsection
