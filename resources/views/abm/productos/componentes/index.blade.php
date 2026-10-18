@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Componentes estándar</h1>

            <p class="text-muted mb-0">
                {{ $producto->codigo }} - {{ $producto->nombre }}
            </p>
        </div>

        <div>
            <a
                href="{{ route('productos.componentes.create', $producto) }}"
                class="btn btn-primary"
            >
                + Agregar componente
            </a>

            <a
                href="{{ route('productos.index') }}"
                class="btn btn-outline-secondary"
            >
                Volver
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Componente</th>
                        <th>Cantidad</th>
                        <th>Obligatorio</th>
                        <th>Uso</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($producto->componentesEstandar as $componente)
                        <tr>
                            <td class="fw-semibold">
                                {{ $componente->componenteProducto?->nombre ?? '-' }}
                            </td>

                            <td>
                                {{ number_format((float) $componente->cantidad, 2, ',', '.') }}
                            </td>

                            <td>
                                {{ $componente->obligatorio ? 'Sí' : 'No' }}
                            </td>

                            <td>
                                {{ ucfirst($componente->uso) }}
                            </td>

                            <td>
                                <span class="badge {{ $componente->activo ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $componente->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'productos.componentes.destroy',
                                        [$producto, $componente]
                                    ) }}"
                                    onsubmit="return confirm('¿Eliminar este componente?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-danger">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No hay componentes estándar configurados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
