@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-1">Planes de producción</h1>
                <p class="text-muted mb-0">
                    Planes registrados en aP
                </p>
            </div>

            <a href="{{ route('planes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Nuevo plan
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Plan</th>
                                <th>Productos</th>
                                <th>Estado</th>
                                <th>Fecha planificada</th>
                                <th>Prioridad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($planes as $plan)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $plan->numero }}
                                    </td>

                                    <td>
                                        @foreach ($plan->planProductos as $planProducto)
                                            <div>
                                                {{ $planProducto->producto?->nombre ?? 'Producto no encontrado' }}
                                                <small class="text-muted">
                                                    (Cantidad: {{ $planProducto->cantidad }})
                                                </small>
                                            </div>
                                        @endforeach
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            {{ ucfirst($plan->estado) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $plan->fecha_planificada?->format('d/m/Y') ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $plan->prioridad }}
                                    </td>


                                    <td>
                                        <a href="{{ route('planes.show', $plan) }}" class="btn btn-sm btn-primary">
                                            Ver
                                        </a>
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No hay planes registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $planes->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>

    </div>
@endsection
