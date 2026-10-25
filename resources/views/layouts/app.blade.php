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

    <style>
        .menu-lateral {
            width: 260px;
            flex: 0 0 260px;
            transition: width 0.25s ease, padding 0.25s ease;
            overflow: hidden;
        }

        .contenido-principal {
            flex: 1 1 auto;
            min-width: 0;
        }

        body.menu-colapsado .menu-lateral {
            width: 0;
            flex-basis: 0;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        @media (max-width: 767.98px) {
            .menu-lateral {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-light">

    <nav
        class="navbar navbar-dark shadow-sm"
        style="background: linear-gradient(135deg, #585858, #f28c00);"
    >
        <div class="container-fluid px-3 px-md-4">

            <div class="d-flex align-items-center">

                {{-- Menú móvil --}}
                <button
                    class="btn btn-outline-light d-md-none me-2"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#menuMovil"
                    aria-controls="menuMovil"
                    aria-label="Abrir menú"
                >
                    <i class="bi bi-list fs-5"></i>
                </button>

                {{-- Colapsar menú de escritorio --}}
                <button
                    id="botonMenuEscritorio"
                    class="btn btn-outline-light d-none d-md-inline-flex me-2"
                    type="button"
                    aria-label="Mostrar u ocultar menú"
                    title="Mostrar u ocultar menú"
                >
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>

                <a
                    href="{{ route('dashboard') }}"
                    class="navbar-brand fw-bold"
                >
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

                        <button
                            type="submit"
                            class="btn btn-sm btn-outline-light"
                        >
                            <i class="bi bi-box-arrow-right me-1"></i>
                            <span class="d-none d-sm-inline">
                                Salir
                            </span>
                        </button>
                    </form>

                </div>
            @endauth

        </div>
    </nav>

    {{-- Menú móvil --}}
    <div
        class="offcanvas offcanvas-start bg-dark text-white"
        tabindex="-1"
        id="menuMovil"
        aria-labelledby="menuMovilLabel"
    >
        <div class="offcanvas-header">
            <h5
                class="offcanvas-title text-warning"
                id="menuMovilLabel"
            >
                Menú principal
            </h5>

            <button
                type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"
                aria-label="Cerrar"
            >
            </button>
        </div>

        <div class="offcanvas-body p-3">
            @include('layouts.menu')
        </div>
    </div>

    {{-- Contenido principal --}}
    <div class="container-fluid p-0">
        <div class="d-flex">

            {{-- Menú lateral de escritorio --}}
            <aside
                id="menuLateral"
                class="menu-lateral bg-dark text-white min-vh-100 p-3 d-none d-md-block"
            >
                @include('layouts.menu')
            </aside>

            {{-- Contenido --}}
            <main class="contenido-principal p-3 p-md-4">
                @yield('content')
            </main>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const boton = document.getElementById('botonMenuEscritorio');

            if (!boton) {
                return;
            }

            const estadoGuardado =
                localStorage.getItem('menu-colapsado') === 'true';

            if (estadoGuardado) {
                document.body.classList.add('menu-colapsado');
            }

            boton.addEventListener('click', function () {
                document.body.classList.toggle('menu-colapsado');

                localStorage.setItem(
                    'menu-colapsado',
                    document.body.classList.contains('menu-colapsado')
                );
            });
        });
    </script>

</body>

</html>
