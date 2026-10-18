@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <h1 class="mb-4">Nuevo cliente</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('clientes.store') }}">
                @csrf

                @include('abm.clientes._form')

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Guardar cliente
                    </button>

                    <a href="{{ route('clientes.index') }}"
                       class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
