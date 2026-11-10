<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>App Producción</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.pwa-produccion')
</head>

<body style="background: linear-gradient(180deg, #173e4c 48%, #edf3f4 48%);">
    <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="width: 100%; max-width: 470px;">

            <div class="text-center text-white p-4"
                 style="background: linear-gradient(135deg, #173e4c, #17665d);">

                <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                     style="width: 75px; height: 75px;">
                    <img src="{{ asset('logos/ImgLogoCircular-SF-r.png') }}" width="58" height="58" class="object-fit-contain" alt="">
                </div>

                <h2 class="fw-bold mb-1">App Producción</h2>
                <p class="mb-0">Gestión de producción</p>
            </div>

            <div class="card-body p-4">
                <h4 class="text-center fw-bold mb-4">Ingresar</h4>

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Correo electrónico
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email" autocomplete="username"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            required
                            autofocus
                        >

                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Contraseña
                        </label>

                        <div class="input-group has-validation">
                        <input
                            id="password"
                            type="password"
                            name="password" autocomplete="current-password"
                            class="form-control @error('password') is-invalid @enderror
                        </div>"
                            required
                        >

                        <button type="button" class="btn btn-outline-secondary" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="form-check-input"
                        >

                        <label for="remember_me" class="form-check-label">
                            Recordarme
                        </label>
                    </div>

                    <div class="d-grid gap-3">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-success">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif

                        <button type="submit" class="btn btn-success rounded-pill py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Ingresar
                        </button>
                    </div>
                </form>
                <button type="button" class="btn btn-outline-success w-100 mt-3" data-install-produccion hidden>
                    Instalar App Producción
                </button>
                <p class="small text-muted mt-3 mb-0" data-ios-produccion hidden>
                    En Safari, tocá <strong>Compartir</strong> → <strong>Agregar a inicio</strong> → <strong>Agregar</strong>.
                </p>
            </div>

            <div class="card-footer text-center text-muted">
                App Producción
            </div>
        </div>
    </main>
</body>
</html>
