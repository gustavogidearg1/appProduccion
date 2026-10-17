<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard - App Producción</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark shadow-sm"
         style="background: linear-gradient(135deg, #555555, #f28c00);">
        <div class="container-fluid px-4">
            <a href="{{ route('dashboard') }}"
               class="navbar-brand fw-bold">
                <i class="bi bi-gear-wide-connected me-2"></i>
                App Producción
            </a>

            <div class="d-flex align-items-center gap-3 text-white">
                <span>
                    <i class="bi bi-person-circle me-1"></i>
                    {{ Auth::user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">

            <aside class="col-md-3 col-lg-2 bg-dark text-white min-vh-100 p-3">
                <h6 class="text-uppercase text-warning mb-3">
                    Menú principal
                </h6>

                <div class="nav flex-column gap-2">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link active text-white bg-warning rounded">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Tablero
                    </a>

                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-calendar3 me-2"></i>
                        Planificación
                    </a>

                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-kanban me-2"></i>
                        Producción
                    </a>

                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-box-seam me-2"></i>
                        Chasis
                    </a>

                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-check2-square me-2"></i>
                        Calidad
                    </a>

                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-bar-chart-line me-2"></i>
                        Reportes
                    </a>
                </div>
            </aside>

            <main class="col-md-9 col-lg-10 p-4">

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

                <div class="row g-3 mb-4">

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

            </main>
        </div>
    </div>

</body>
</html>
