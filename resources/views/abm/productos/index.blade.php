@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-1">Productos</h1>
                <p class="text-muted mb-0">
                    Catálogo conectado con Cotizador
                </p>
            </div>

            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                Volver al tablero
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">

                <form method="GET" action="{{ route('productos.index') }}" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control"
                            placeholder="Buscar por código o nombre..." value="{{ $search }}">

                        <button type="submit" class="btn btn-primary">
                            Buscar
                        </button>

                        @if ($search)
                            <a href="{{ route('productos.index') }}" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Familia</th>
                                <th>UE producción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($productos as $producto)
                                <tr>
                                    <td>{{ $producto->codigo }}</td>

                                    <td class="fw-semibold">
                                        {{ $producto->nombre }}
                                    </td>

                                    <td>
                                        {{ $producto->familia?->nombre ?? '-' }}
                                    </td>

                                    <td>
                                        @if (!is_null($producto->ue_produccion))
                                            {{ number_format((float) $producto->ue_produccion, 2, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        @if ($producto->activo)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>

<td>
@if(strtolower($producto->tipo?->nombre ?? '') === 'implemento')
    <a
        href="{{ route('productos.componentes.index', $producto) }}"
        class="btn btn-sm btn-info"
        title="Configurar accesorios estándar"
    >
        Accesorios estándar
    </a>
@endif
</td>

                                </tr>
                            @empty
                                <tr>
<td colspan="6" class="text-center text-muted py-4">
    No se encontraron productos.
</td>
La column
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $productos->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>

    </div>
@endsection
