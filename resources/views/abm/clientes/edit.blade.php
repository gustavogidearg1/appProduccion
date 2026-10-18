@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <h1 class="mb-4">Editar cliente</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('clientes.update', $cliente) }}">
                @csrf
                @method('PUT')

                @include('abm.clientes._form')

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Actualizar cliente
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
