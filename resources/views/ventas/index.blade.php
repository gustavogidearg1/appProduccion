@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Ventas</h1>
                <p class="text-muted mb-0">
                    Consultá ventas, productos disponibles y unidades en producción.
                </p>
            </div>

            <a href="{{ route('ventas.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Nueva venta
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Disponibilidad para vender --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h4 class="mb-0">Disponibilidad para vender</h4>
            </div>

            <div class="card-body">

                <form method="GET" action="{{ route('ventas.index') }}" class="mb-4">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-4">
                            <label for="familia_id" class="form-label">
                                Familia
                            </label>

                            <select name="familia_id" id="familia_id" class="form-select">
                                <option value="">Todas las familias</option>

                                @foreach ($familias as $familia)
                                    <option value="{{ $familia->id }}" @selected((string) ($filtrosDisponibilidad['familia_id'] ?? '') === (string) $familia->id)>
                                        {{ $familia->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label for="disponibilidad_producto_id" class="form-label">
                                Producto
                            </label>

                            <select name="disponibilidad_producto_id" id="disponibilidad_producto_id" class="form-select">
                                <option value="">Todos los productos</option>

                                @foreach ($productosDisponibles as $producto)
                                    <option value="{{ $producto->id }}" @selected((string) ($filtrosDisponibilidad['producto_id'] ?? '') === (string) $producto->id)>
                                        {{ $producto->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>
                                Filtrar
                            </button>

                            <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>

                    </div>
                </form>

                <p class="text-muted small">Abrí una familia y luego un producto para ver sus unidades disponibles y elegir cuál vender.</p>
                @php
                    $disponibilidadPorFamilia = $unidadesDisponibles
                        ->groupBy(fn ($unidad) => $unidad->planProducto?->producto?->familia_id ?? 'sin-familia')
                        ->sortBy(fn ($unidades) => $unidades->first()->planProducto?->producto?->familia?->nombre ?? 'Sin familia');
                @endphp
                @forelse ($disponibilidadPorFamilia as $unidadesFamilia)
                    @php
                        $familiaDisponible = $unidadesFamilia->first()->planProducto?->producto?->familia;
                        $porProducto = $unidadesFamilia->groupBy(fn ($unidad) => $unidad->planProducto?->producto_id ?? 'sin-producto')
                            ->sortBy(fn ($unidades) => $unidades->first()->planProducto?->producto?->nombre ?? '');
                    @endphp
                    <details class="border rounded mb-3" @if (!empty($filtrosDisponibilidad['familia_id']) || !empty($filtrosDisponibilidad['producto_id'])) open @endif>
                        <summary class="p-3 bg-light" style="cursor: pointer;">
                            <strong>{{ $familiaDisponible?->nombre ?? 'Sin familia' }}</strong>
                            <span class="badge bg-primary ms-2">{{ $unidadesFamilia->count() }} unidades disponibles</span>
                            <span class="text-muted small ms-2">{{ $porProducto->count() }} productos</span>
                        </summary>
                        <div class="p-3">
                            @foreach ($porProducto as $unidadesProducto)
                                @php
                                    $productoDisponible = $unidadesProducto->first()->planProducto?->producto;
                                @endphp
                                <details class="border rounded mb-2" @if (!empty($filtrosDisponibilidad['producto_id'])) open @endif>
                                    <summary class="p-3" style="cursor: pointer;">
                                        <strong>{{ $productoDisponible?->nombre ?? 'Producto no disponible' }}</strong>
                                        <span class="badge bg-secondary ms-2">{{ $unidadesProducto->count() }} disponibles</span>
                                    </summary>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Chasis</th>
                                <th>Estado</th>
                                <th>Disponibilidad</th>
                                <th>Entrega estimada</th>
                                <th>Acción</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($unidadesProducto as $unidad)
                                @php
                                    $producto = $unidad->planProducto?->producto;

                                    $avanceActual = $unidad->avances
                                        ->whereIn('estado', ['en_proceso', 'completada'])
                                        ->sortByDesc(fn($avance) => $avance->etapa?->orden ?? 0)
                                        ->first();

                                    $estadoActual =
                                        $avanceActual?->etapa?->nombre ?? ($unidad->estado ?? 'Planificada');

                                    $estimacion = $unidad->estimacion_entrega;
                                    $esStock = $estimacion['inmediata'];
                                @endphp

                                <tr>
                                    <td>
                                        @if ($unidad->chasis)
                                            <span class="badge bg-dark">
                                                {{ $unidad->chasis }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $estimacion['excluida'] ? $estimacion['texto'] : $estimacion['etapa'] }}
                                        </span>
                                        @if ($estimacion['origen'] === 'plan')<div class="small text-muted">Etapa del plan</div>@endif
                                    </td>

                                    <td>
                                        @if ($esStock)
                                            <span class="badge bg-success">
                                                Stock disponible
                                            </span>
                                        @else
                                            <span class="badge bg-primary">
                                                Producción disponible
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @include('planificacion.estimacion', ['estimacion'=>$estimacion])
                                    </td>

                                    <td>
                                        @if ($producto)
                                            <a href="{{ route('ventas.create', [
                                                'producto_id' => $unidad->planProducto->producto_id,
                                                'unidad_id' => $unidad->id,
                                            ]) }}"
                                                class="btn btn-success">
                                                <i class="bi bi-cart-plus me-1"></i>
                                                Vender
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>

                            @endforeach
                        </tbody>
                    </table>
                </div>

                                </details>
                            @endforeach
                        </div>
                    </details>
                @empty
                    <div class="text-muted text-center py-4">No hay unidades disponibles para vender.</div>
                @endforelse

            </div>
        </div>

        {{-- Filtros de ventas --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h4 class="mb-0">Filtros de ventas</h4>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('ventas.index') }}">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-2">
                            <label for="fecha_desde" class="form-label">
                                Fecha desde
                            </label>

                            <input type="date" name="fecha_desde" id="fecha_desde" class="form-control"
                                value="{{ $filtros['fecha_desde'] ?? '' }}">
                        </div>

                        <div class="col-md-2">
                            <label for="fecha_hasta" class="form-label">
                                Fecha hasta
                            </label>

                            <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control"
                                value="{{ $filtros['fecha_hasta'] ?? '' }}">
                        </div>

                        <div class="col-md-2">
                            <label for="cliente_id" class="form-label">
                                Cliente
                            </label>

                            <select name="cliente_id" id="cliente_id" class="form-select">
                                <option value="">Todos</option>

                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected((string) ($filtros['cliente_id'] ?? '') === (string) $cliente->id)>
                                        {{ $cliente->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="producto_id" class="form-label">
                                Producto
                            </label>

                            <select name="producto_id" id="producto_id" class="form-select">
                                <option value="">Todos</option>

                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}" @selected((string) ($filtros['producto_id'] ?? '') === (string) $producto->id)>
                                        {{ $producto->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="chasis" class="form-label">
                                Chasis
                            </label>

                            <input type="text" name="chasis" id="chasis" class="form-control"
                                placeholder="Ej.: 20517" value="{{ $filtros['chasis'] ?? '' }}">
                        </div>

                        <div class="col-md-2">
                            <label for="estado" class="form-label">
                                Estado
                            </label>

                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="borrador" @selected(($filtros['estado'] ?? '') === 'borrador')>
                                    Borrador
                                </option>
                                <option value="confirmada" @selected(($filtros['estado'] ?? '') === 'confirmada')>
                                    Confirmada
                                </option>
                                <option value="entregada" @selected(($filtros['estado'] ?? '') === 'entregada')>
                                    Entregada
                                </option>
                                <option value="anulada" @selected(($filtros['estado'] ?? '') === 'anulada')>
                                    Anulada
                                </option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="asignacion" class="form-label">
                                Asignación
                            </label>

                            <select name="asignacion" id="asignacion" class="form-select">
                                <option value="">Todas</option>
                                <option value="asignada" @selected(($filtros['asignacion'] ?? '') === 'asignada')>
                                    Asignadas
                                </option>
                                <option value="sin_asignar" @selected(($filtros['asignacion'] ?? '') === 'sin_asignar')>
                                    Sin asignar
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>
                                Filtrar
                            </button>

                            <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- Historial de ventas --}}
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0">Ventas registradas</h4>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N.º</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Productos</th>
                                <th>Chasis</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($ventas as $venta)
                                <tr>
                                    <td>
                                        {{ $venta->id }}
                                    </td>

                                    <td>
                                        {{ $venta->cliente?->nombre ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $venta->fecha?->format('d/m/Y') ?? '-' }}
                                    </td>

                                    <td>
                                        @foreach ($venta->detalles as $detalle)
                                            <div class="mb-1">
                                                <strong>
                                                    {{ $detalle->producto?->nombre ?? 'Producto no disponible' }}
                                                </strong>

                                                <small class="text-muted">
                                                    x {{ number_format((float) $detalle->cantidad, 2, ',', '.') }}
                                                </small>
                                            </div>
                                        @endforeach
                                    </td>

                                    <td>
                                        @foreach ($venta->detalles as $detalle)
                                            @foreach ($detalle->asignaciones as $asignacion)
                                                @if ($asignacion->unidad?->chasis)
                                                    <span class="badge bg-dark mb-1">
                                                        {{ $asignacion->unidad->chasis }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            {{ ucfirst($venta->estado) }}
                                        </span>
                                    </td>

                                    <td>
                                        $ {{ number_format((float) $venta->total, 2, ',', '.') }}
                                    </td>

                                    <td>
                                        <a href="{{ route('ventas.show', $venta) }}" class="btn btn-sm btn-primary">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No hay ventas registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($ventas, 'links'))
                    <div class="p-3">
                        {{ $ventas->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
