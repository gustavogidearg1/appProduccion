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

    <nav class="navbar navbar-dark shadow-sm"
         style="background: linear-gradient(135deg, #585858, #f28c00);">

        <div class="container-fluid px-3 px-md-4">

            <div class="d-flex align-items-center">
                <button
                    class="btn btn-outline-light d-md-none me-2"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#menuMovil"
                    aria-controls="menuMovil"
                    aria-label="Abrir menú">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <a href="{{ route('dashboard') }}"
                   class="navbar-brand fw-bold">
                    <i class="bi bi-gear-wide-connected me-2"></i>
                    <span>App Producción</span>
                </a>
            </div>

            @auth
                <div class="d-flex align-items-center gap-2 gap-md-3 text-white">

                    <span class="d-none d-sm-inline">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ Auth::user()->name }}
                    </span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit"
                                class="btn btn-sm btn-outline-light">
                            <i class="bi bi-box-arrow-right me-1"></i>
                            <span class="d-none d-sm-inline">Salir</span>
                        </button>
                    </form>

                </div>
            @endauth

        </div>
    </nav>

    {{-- Menú móvil --}}
    <div class="offcanvas offcanvas-start bg-dark text-white"
         tabindex="-1"
         id="menuMovil"
         aria-labelledby="menuMovilLabel">

        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-warning"
                id="menuMovilLabel">
                Menú principal
            </h5>

            <button type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="offcanvas"
                    aria-label="Cerrar">
            </button>
        </div>

        <div class="offcanvas-body p-3">
            @include('layouts.menu')
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">

            {{-- Menú lateral para PC --}}
            <aside class="col-md-3 col-lg-2 bg-dark text-white min-vh-100 p-3 d-none d-md-block">
                @include('layouts.menu')
            </aside>

            {{-- Contenido --}}
            <main class="col-12 col-md-9 col-lg-10 p-3 p-md-4">
                @yield('content')
            </main>

        </div>
    </div>

</body>

</html>
