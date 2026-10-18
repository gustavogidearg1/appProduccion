@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Componentes y accesorios</h1>

                <p class="text-muted mb-0">
                    Plan #{{ $plan->numero }} -
                    Unidad {{ $unidad->numero_unidad }} -
                    {{ $unidad->planProducto->producto?->nombre ?? '-' }}
                </p>
            </div>

            <div>
                <a href="{{ route('planes.productos.unidades.componentes.create', [$plan, $planProducto, $unidad]) }}"
                    class="btn btn-primary">
                    + Agregar componente
                </a>

                <a href="{{ route('planes.productos.unidades', [$plan, $planProducto]) }}" class="btn btn-outline-secondary">
                    Volver
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Componente</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Uso</th>
                                <th>Trazabilidad</th>
                                <th>Fecha instalación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($unidad->componentes as $componente)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $componente->producto?->nombre ?? 'Producto no disponible' }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $componente->cantidad, 2, ',', '.') }}
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            {{ ucfirst($componente->estado) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ ucfirst($componente->uso) }}
                                    </td>

                                    <td>
                                        {{ $componente->trazabilidad ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $componente->fecha_instalacion?->format('d/m/Y') ?? '-' }}
                                    </td>

                                    <td>
                                        <a href="{{ route('planes.productos.unidades.componentes.edit', [$plan, $planProducto, $unidad, $componente]) }}"
                                            class="btn btn-sm btn-warning">
                                            Editar
                                        </a>
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No hay componentes cargados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
