@extends('layouts.app')

@section('title', 'Tablero de producción')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="fw-bold mb-1">Tablero de producción</h1>

        <p class="text-muted mb-0">
            Estado general de los planes productivos
        </p>
    </div>

    <span class="badge text-bg-warning fs-6">
        Datos de ejemplo
    </span>
</div>

@php
    $etapas = [
        ['nombre' => 'Corte', 'cantidad' => 12, 'icono' => 'scissors', 'color' => 'primary'],
        ['nombre' => 'Plegado', 'cantidad' => 8, 'icono' => 'layers', 'color' => 'info'],
        ['nombre' => 'Armado', 'cantidad' => 6, 'icono' => 'tools', 'color' => 'warning'],
        ['nombre' => 'Escoriado', 'cantidad' => 5, 'icono' => 'brush', 'color' => 'secondary'],
        ['nombre' => 'Pintura', 'cantidad' => 4, 'icono' => 'paint-bucket', 'color' => 'danger'],
        ['nombre' => 'Terminado', 'cantidad' => 3, 'icono' => 'check-circle', 'color' => 'success'],
        ['nombre' => 'OK Calidad', 'cantidad' => 2, 'icono' => 'patch-check', 'color' => 'dark'],
    ];
@endphp

<div class="row g-3 mb-4">

    @foreach ($etapas as $etapa)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">
                                {{ $etapa['nombre'] }}
                            </p>

                            <h2 class="fw-bold mb-0">
                                {{ $etapa['cantidad'] }}
                            </h2>

                            <small class="text-muted">
                                planes en etapa
                            </small>
                        </div>

                        <div class="rounded-circle bg-{{ $etapa['color'] }} text-white p-3">
                            <i class="bi bi-{{ $etapa['icono'] }} fs-4"></i>
                        </div>
                    </div>

                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-{{ $etapa['color'] }}"
                             style="width: {{ min($etapa['cantidad'] * 8, 100) }}%;">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endforeach

</div>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-list-check text-warning me-2"></i>
            Planes recientes
        </h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Plan</th>
                    <th>Producto</th>
                    <th>Chasis</th>
                    <th>Etapa actual</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td class="fw-bold">900</td>
                    <td>Tolva autodescargable</td>
                    <td>CH-1001</td>
                    <td>Corte</td>
                    <td>
                        <span class="badge text-bg-primary">
                            En producción
                        </span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">901</td>
                    <td>Batea agrícola</td>
                    <td>CH-1002</td>
                    <td>Pintura</td>
                    <td>
                        <span class="badge text-bg-warning">
                            En proceso
                        </span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">902</td>
                    <td>Tolva fertilizante</td>
                    <td>CH-1003</td>
                    <td>OK Calidad</td>
                    <td>
                        <span class="badge text-bg-success">
                            Listo
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

@endsection
