<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'App Producción') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="card border-0 shadow" style="width: 100%; max-width: 430px;">
            <div class="text-center p-4 pb-2">
                <div class="rounded-circle bg-warning d-inline-flex align-items-center justify-content-center mb-3"
                     style="width: 70px; height: 70px;">
                    <i class="bi bi-gear-wide-connected fs-1 text-dark"></i>
                </div>

                <h2 class="fw-bold mb-1">App Producción</h2>
                <p class="text-muted mb-0">Gestión de producción</p>
            </div>

            <div class="card-body p-4">
                {{ $slot }}
            </div>
        </div>
    </main>
</body>
</html>

