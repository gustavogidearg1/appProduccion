<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'App Producción')
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark shadow-sm" style="background: linear-gradient(135deg, #585858, #f28c00);">
        <div class="container-fluid px-4">

            <a href="{{ route('dashboard') }}" class="navbar-brand fw-bold">
                <i class="bi bi-gear-wide-connected me-2"></i>
                App Producción
            </a>

            @auth
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
            @endauth

        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">

            <aside class="col-md-3 col-lg-2 bg-dark text-white min-vh-100 p-3">

                <h6 class="text-uppercase text-warning mb-3">
                    Menú principal
                </h6>

                <nav class="nav flex-column gap-2">

                    <a href="{{ route('dashboard') }}"
                        class="nav-link text-white rounded
                       {{ request()->routeIs('dashboard') ? 'bg-warning' : '' }}">
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



                    <a href="{{ route('productos.index') }}"
                        class="nav-link rounded
   {{ request()->routeIs('productos.*') ? 'bg-warning text-dark' : 'text-white' }}">
                        <i class="bi bi-boxes me-2"></i>
                        Productos
                    </a>

                                        <a href="{{ route('clientes.index') }}"
                        class="nav-link rounded
   {{ request()->routeIs('clientes.*') ? 'bg-warning text-dark' : 'text-white' }}">
                        <i class="bi bi-people me-2"></i>
                        Clientes
                    </a>

                                                            <a href="{{ route('planes.index') }}"
                        class="nav-link rounded
   {{ request()->routeIs('planes.*') ? 'bg-warning text-dark' : 'text-white' }}">
                        <i class="bi bi-people me-2"></i>
                        Planes de producción
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

                </nav>
            </aside>

            <main class="col-md-9 col-lg-10 p-4">
                @yield('content')
            </main>

        </div>
    </div>

</body>

</html>
