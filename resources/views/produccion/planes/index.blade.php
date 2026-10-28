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

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('planes.index') }}">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-3">
                            <label for="fecha_desde" class="form-label">
                                Fecha desde
                            </label>

                            <input type="date" id="fecha_desde" name="fecha_desde" class="form-control"
                                value="{{ $filtros['fecha_desde'] ?? '' }}">
                        </div>

                        <div class="col-md-3">
                            <label for="fecha_hasta" class="form-label">
                                Fecha hasta
                            </label>

                            <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control"
                                value="{{ $filtros['fecha_hasta'] ?? '' }}">
                        </div>

                        <div class="col-md-3">
                            <label for="estado" class="form-label">
                                Estado del plan
                            </label>

                            <select id="estado" name="estado" class="form-select">
                                <option value="">Todos</option>

                                <option value="abierto" @selected(($filtros['estado'] ?? '') === 'abierto')>
                                    Abierto
                                </option>

                                <option value="cerrado" @selected(($filtros['estado'] ?? '') === 'cerrado')>
                                    Cerrado
                                </option>

                                <option value="cancelado" @selected(($filtros['estado'] ?? '') === 'cancelado')>
                                    Anulado
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="etapa_id" class="form-label">
                                Etapa productiva
                            </label>

                            <select id="etapa_id" name="etapa_id" class="form-select">
                                <option value="">Todas</option>

                                <option value="planificada" @selected(($filtros['etapa_id'] ?? '') === 'planificada')>
                                    Planificada
                                </option>

                                @foreach ($etapas as $etapa)
                                    <option value="{{ $etapa->id }}" @selected((string) ($filtros['etapa_id'] ?? '') === (string) $etapa->id)>
                                        {{ $etapa->nombre }}
                                    </option>
                                @endforeach

                                <option value="entregada" @selected(($filtros['etapa_id'] ?? '') === 'entregada')>
                                    Entregada
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="familia_id" class="form-label">Familia</label>
                            <select name="familia_id" id="familia_id" class="form-select">
                                <option value="">Todas las familias</option>
                                @foreach ($familias as $familia)<option value="{{ $familia->id }}" @selected((string)($filtros['familia_id'] ?? '') === (string)$familia->id)>{{ $familia->nombre }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="producto_id" class="form-label">Producto</label>
                            <select name="producto_id" id="producto_id" class="form-select">
                                <option value="">Todos los productos</option>
                                @foreach ($productosFiltro as $producto)<option value="{{ $producto->id }}" data-familia="{{ $producto->familia_id }}" @selected((string)($filtros['producto_id'] ?? '') === (string)$producto->id)>{{ $producto->nombre }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="chasis" class="form-label">Chasis</label>
                            <input type="search" name="chasis" id="chasis" class="form-control" maxlength="100" placeholder="Completo o parte del número" value="{{ $filtros['chasis'] ?? '' }}">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>
                                Filtrar
                            </button>

                            <a href="{{ route('planes.index') }}" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Plan</th>
                                <th>Productos</th>
                                <th>Estado</th>
                                <th>Ventas / asignación</th>
                                <th>Fecha planificada</th>
                                <th>Fin estimado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($planes as $plan)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $plan->numero }}
                                    </td>

                                    <td>
                                        @foreach ($plan->planProductos as $planProducto)
                                            <div>
                                                {{ $planProducto->producto?->nombre ?? 'Producto no encontrado' }}

                                                <small class="text-muted">
                                                    (Cantidad:
                                                    {{ number_format((float) $planProducto->cantidad, 2, ',', '.') }})
                                                </small>
                                                <div class="small text-muted">
                                                    Vinculadas a ventas: {{ $planProducto->resumen_ventas['total'] }}
                                                    · Reservadas: {{ $planProducto->resumen_ventas['reservadas'] }}
                                                    · Asignadas: {{ $planProducto->resumen_ventas['asignadas'] }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </td>

                                    <td>
                                        @php
                                            $estadosPlan = $plan->planProductos
                                                ->flatMap(fn($planProducto) => $planProducto->unidadesProduccion)
                                                ->map(function ($unidad) {
                                                    $avanceActual = $unidad->avances
                                                        ->where('estado', 'en_proceso')
                                                        ->sortBy(fn($avance) => $avance->etapa?->orden ?? 999)
                                                        ->first();

                                                    $ultimaEtapa = $unidad->avances
                                                        ->where('estado', 'completada')
                                                        ->sortByDesc(fn($avance) => $avance->etapa?->orden ?? 0)
                                                        ->first();

                                                    return $avanceActual?->etapa?->nombre ??
                                                        ($ultimaEtapa?->etapa?->nombre ?? 'Planificada');
                                                })
                                                ->countBy();
                                        @endphp

                                        @if ($estadosPlan->isEmpty())
                                            <span class="badge bg-secondary">
                                                Planificada
                                            </span>
                                        @else
                                            @foreach ($estadosPlan as $nombre => $cantidad)
                                                <span class="badge bg-warning text-dark mb-1">
                                                    {{ $nombre }}: {{ $cantidad }}
                                                </span>
                                            @endforeach
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold mb-1">Vinculadas a ventas: {{ $plan->resumen_ventas['total'] }}</div>
                                        <span class="badge bg-info text-dark">Reservadas: {{ $plan->resumen_ventas['reservadas'] }}</span>
                                        <span class="badge bg-success">Asignadas: {{ $plan->resumen_ventas['asignadas'] }}</span>
                                    </td>

                                    <td>
                                        {{ $plan->fecha_planificada?->format('d/m/Y') ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $plan->fecha_fin_planificada?->format('d/m/Y') ?? '-' }}
                                    </td>

                                    <td>
                                        <a href="{{ ($plan->producto_destino_id ? route('planes.productos.unidades', [$plan, $plan->producto_destino_id]) : route('planes.show', $plan)) }}" class="btn btn-sm btn-primary">
                                            Ver
                                        </a>
                                        <a href="{{ route('planes.estado.index',$plan) }}" class="btn btn-sm btn-outline-danger mt-1">{{ $plan->estado==='cancelado' ? 'Reactivar / historial' : 'Anular / historial' }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No hay planes que coincidan con los filtros.
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
    <script>
        (() => {
            const familia = document.getElementById('familia_id');
            const producto = document.getElementById('producto_id');
            function filtrarProductos(resetear) {
                for (const opcion of producto.options) {
                    const visible = !opcion.value || !familia.value || opcion.dataset.familia === familia.value;
                    opcion.hidden = !visible;
                    opcion.disabled = !visible;
                }
                if (resetear && producto.selectedOptions[0]?.disabled) producto.value = '';
            }
            familia.addEventListener('change', () => filtrarProductos(true));
            filtrarProductos(false);
        })();
    </script>
@endsection
