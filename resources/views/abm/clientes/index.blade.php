@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Clientes</h1>
            <p class="text-muted mb-0">
                Clientes registrados para Ventas y Producción
            </p>
        </div>

        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Nuevo cliente
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="GET" action="{{ route('clientes.index') }}" class="mb-4">
                <div class="input-group">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Buscar por nombre, CUIT o email..."
                        value="{{ $search }}"
                    >

                    <button class="btn btn-primary" type="submit">
                        Buscar
                    </button>

                    @if($search)
                        <a href="{{ route('clientes.index') }}"
                           class="btn btn-outline-secondary">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>CUIT</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($clientes as $cliente)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $cliente->nombre }}
                                </td>

                                <td>{{ $cliente->cuit ?? '-' }}</td>
                                <td>{{ $cliente->email ?? '-' }}</td>
                                <td>{{ $cliente->telefono ?? '-' }}</td>

                                <td>
                                    @if($cliente->activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('clientes.edit', $cliente) }}"
                                       class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <form action="{{ route('clientes.destroy', $cliente) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Eliminar este cliente?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-danger">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="text-center text-muted py-4">
                                    No hay clientes registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $clientes->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>

</div>
@endsection
