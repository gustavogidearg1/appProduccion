<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>App Producción</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="card border-0 shadow" style="width: 100%; max-width: 430px;">

            <div class="text-center text-white p-4"
                 style="background: linear-gradient(135deg, #ff9900, #e58900);">

                <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                     style="width: 75px; height: 75px;">
                    <i class="bi bi-gear-wide-connected text-warning"
                       style="font-size: 42px;"></i>
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
                            name="email"
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

                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            required
                        >

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

                    <div class="d-flex justify-content-between align-items-center">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-warning">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif

                        <button type="submit" class="btn btn-warning px-4">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Ingresar
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-footer text-center text-muted">
                App Producción
            </div>
        </div>
    </main>
</body>
</html>
