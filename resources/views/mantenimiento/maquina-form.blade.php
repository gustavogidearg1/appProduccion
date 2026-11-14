@extends('layouts.app')
@section('content')
<h1>Nueva máquina</h1>@include('mantenimiento.nav')
<form class="card card-body" method="post" action="{{ route('mantenimiento.maquinas.crear') }}">@csrf
@include('mantenimiento.maquina-campos')<button class="btn btn-success mt-3">Guardar máquina</button></form>
@endsection
