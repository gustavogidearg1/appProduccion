<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaAsignacion;
use App\Models\Familia;
use App\Models\UnidadProduccion;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index(Request $request)
{
    $filtros = [
        'cliente_id' => $request->input('cliente_id'),
        'producto_id' => $request->input('producto_id'),
        'chasis' => $request->input('chasis'),
        'estado' => $request->input('estado'),
        'asignacion' => $request->input('asignacion'),
        'fecha_desde' => $request->input('fecha_desde'),
        'fecha_hasta' => $request->input('fecha_hasta'),
    ];

    $clientes = Cliente::query()
        ->orderBy('nombre')
        ->get();

    $productos = Producto::query()
        ->with('tipo')
        ->where('activo', true)
        ->get()
        ->sortBy(fn ($producto) => $this->ordenProducto($producto))
        ->values();

    $consulta = Venta::query()
        ->with([
            'cliente',
            'detalles.producto.tipo',
            'detalles.asignaciones.unidad',
        ]);

    if ($filtros['cliente_id']) {
        $consulta->where('cliente_id', $filtros['cliente_id']);
    }

    if ($filtros['producto_id']) {
        $consulta->whereHas('detalles', function ($query) use ($filtros) {
            $query->where('producto_id', $filtros['producto_id']);
        });
    }

    if ($filtros['chasis']) {
        $consulta->whereHas(
            'detalles.asignaciones.unidad',
            function ($query) use ($filtros) {
                $query->where(
                    'chasis',
                    'like',
                    '%' . $filtros['chasis'] . '%'
                );
            }
        );
    }

    if ($filtros['estado']) {
        $consulta->where('estado', $filtros['estado']);
    }

    if ($filtros['fecha_desde']) {
        $consulta->whereDate(
            'fecha',
            '>=',
            $filtros['fecha_desde']
        );
    }

    if ($filtros['fecha_hasta']) {
        $consulta->whereDate(
            'fecha',
            '<=',
            $filtros['fecha_hasta']
        );
    }

    if ($filtros['asignacion'] === 'sin_asignar') {
        $consulta->whereDoesntHave(
            'detalles.asignaciones',
            function ($query) {
                $query->whereIn(
                    'estado',
                    ['reservada', 'asignada']
                );
            }
        );
    }

    if (in_array($filtros['asignacion'], ['reservada', 'asignada'])) {
        $consulta->whereHas(
            'detalles.asignaciones',
            function ($query) use ($filtros) {
                $query->where(
                    'estado',
                    $filtros['asignacion']
                );
            }
        );
    }

    $ventas = $consulta
        ->orderByDesc('fecha')
        ->orderByDesc('id')
        ->paginate(20)
        ->withQueryString();

    foreach ($ventas as $venta) {
        $venta->setRelation(
            'detalles',
            $venta->detalles
                ->sortBy(fn ($detalle) =>
                    $this->ordenProducto($detalle->producto)
                )
                ->values()
        );
    }

$familias = Familia::query()
    ->orderBy('nombre')
    ->get();

$filtrosDisponibilidad = [
    'familia_id' => $request->input('familia_id'),
    'producto_id' => $request->input('disponibilidad_producto_id'),
];

$productosDisponibles = Producto::query()
    ->with(['familia', 'tipo'])
    ->where('activo', true)
    ->when(
        $filtrosDisponibilidad['familia_id'],
        fn ($query, $familiaId) =>
            $query->where('familia_id', $familiaId)
    )
    ->when(
        $filtrosDisponibilidad['producto_id'],
        fn ($query, $productoId) =>
            $query->whereKey($productoId)
    )
    ->orderBy('nombre')
    ->get();

$productoIdsDisponibles = $productosDisponibles
    ->pluck('id')
    ->map(fn ($id) => (int) $id)
    ->all();

$unidadesDisponibles = UnidadProduccion::query()
    ->with([
        'planProducto.producto.familia',
        'planProducto.producto.tipo',
        'avances.etapa',
        'asignaciones',
    ])
    ->whereDoesntHave('asignaciones', function ($query) {
        $query->whereIn('estado', ['reservada', 'asignada']);
    })
    ->when(
        count($productoIdsDisponibles) > 0,
        fn ($query) =>
            $query->whereHas(
                'planProducto',
                fn ($subQuery) =>
                    $subQuery->whereIn(
                        'producto_id',
                        $productoIdsDisponibles
                    )
            ),
        fn ($query) =>
            $query->whereRaw('1 = 0')
    )
    ->get()
    ->sortBy(function ($unidad) {
        return $this->ordenProducto(
            $unidad->planProducto?->producto
        );
    })
    ->values();

   return view(
    'ventas.index',
    compact(
        'ventas',
        'clientes',
        'productos',
        'filtros',
        'familias',
        'productosDisponibles',
        'unidadesDisponibles',
        'filtrosDisponibilidad'
    )
);
}

    public function create(Request $request)
{
    $clientes = Cliente::query()
        ->orderBy('nombre')
        ->get();

    $productos = Producto::query()
        ->where('activo', true)
        ->orderBy('nombre')
        ->get();

    $productoSeleccionadoId = $request->input('producto_id');

    return view(
        'ventas.create',
        compact(
            'clientes',
            'productos',
            'productoSeleccionadoId'
        )
    );
}

    public function store(Request $request)
{
    $datos = $request->validate([
        'cliente_id' => [
            'required',
            'integer',
            'exists:clientes,id',
        ],

        'fecha' => [
            'required',
            'date',
        ],

        'estado' => [
            'required',
            'string',
            'in:borrador,confirmada,anulada',
        ],

        'observaciones' => [
            'nullable',
            'string',
        ],

        'detalles' => [
            'required',
            'array',
            'min:1',
        ],

        'detalles.*.producto_id' => [
            'required',
            'integer',
        ],

        'detalles.*.cantidad' => [
            'required',
            'numeric',
            'min:0.01',
        ],

        'detalles.*.precio_unitario' => [
            'required',
            'numeric',
            'min:0',
        ],
    ]);

    DB::transaction(function () use ($datos) {
        $total = 0;

        $venta = Venta::create([
            'cliente_id' => $datos['cliente_id'],
            'fecha' => $datos['fecha'],
            'estado' => $datos['estado'],
            'observaciones' => $datos['observaciones'] ?? null,
            'total' => 0,
        ]);

        foreach ($datos['detalles'] as $detalle) {
            $cantidad = (float) $detalle['cantidad'];
            $precio = (float) $detalle['precio_unitario'];
            $subtotal = $cantidad * $precio;

            $venta->detalles()->create([
                'producto_id' => $detalle['producto_id'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $subtotal,
            ]);

            $total += $subtotal;
        }

        $venta->update([
            'total' => $total,
        ]);
    });

    return redirect()
        ->route('ventas.index')
        ->with('success', 'Venta creada correctamente.');
}

    public function show(Venta $venta)
    {
        $venta->load([
            'cliente',
            'detalles.producto.tipo',
            'detalles.asignaciones.unidad.avances.etapa',
        ]);

        $venta->setRelation(
            'detalles',
            $venta->detalles
                ->sortBy(fn ($detalle) => $this->ordenProducto($detalle->producto))
                ->values()
        );

        return view('ventas.show', compact('venta'));
    }

    public function edit(Venta $venta)
    {
        abort_if($venta->estado === 'anulada', 422, 'No se puede editar una venta anulada.');

        $venta->load([
            'cliente',
            'detalles.producto.tipo',
            'detalles.asignaciones',
        ]);

        $venta->setRelation(
            'detalles',
            $venta->detalles
                ->sortBy(fn ($detalle) => $this->ordenProducto($detalle->producto))
                ->values()
        );

        $clientes = Cliente::query()
            ->orderBy('nombre')
            ->get();

        $productos = Producto::query()
            ->with('tipo')
            ->where('activo', true)
            ->get([
                'id',
                'codigo',
                'nombre',
                'tipo_id',
            ])
            ->sortBy(fn ($producto) => $this->ordenProducto($producto))
            ->values();

        return view(
            'ventas.edit',
            compact('venta', 'clientes', 'productos')
        );
    }

    public function update(Request $request, Venta $venta)
    {
        abort_if($venta->estado === 'anulada', 422, 'No se puede editar una venta anulada.');

        $datos = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'fecha' => ['required', 'date'],
            'estado' => ['required', 'in:borrador,confirmada,entregada,anulada'],
            'observaciones' => ['nullable', 'string'],

            'detalles' => ['nullable', 'array'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],

            'nuevos_productos' => ['nullable', 'array'],
            'nuevos_productos.*.producto_id' => ['required', 'integer'],
            'nuevos_productos.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'nuevos_productos.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($datos, $venta) {
            $venta->update([
                'cliente_id' => $datos['cliente_id'],
                'fecha' => $datos['fecha'],
                'estado' => $datos['estado'],
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            foreach ($datos['detalles'] ?? [] as $detalleId => $detalleDatos) {
                $detalle = $venta->detalles()
                    ->whereKey($detalleId)
                    ->with('asignaciones')
                    ->firstOrFail();

                $cantidadAsignada = $detalle->asignaciones
                    ->whereIn('estado', ['reservada', 'asignada'])
                    ->count();

                if ((float) $detalleDatos['cantidad'] < $cantidadAsignada) {
                    abort(
                        422,
                        "La cantidad del producto {$detalle->producto?->nombre} no puede ser menor a las unidades asignadas."
                    );
                }

                $cantidad = (float) $detalleDatos['cantidad'];
                $precio = (float) $detalleDatos['precio_unitario'];

                $detalle->update([
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $cantidad * $precio,
                ]);
            }

            foreach ($datos['nuevos_productos'] ?? [] as $nuevoProducto) {
                $producto = Producto::query()
                    ->whereKey($nuevoProducto['producto_id'])
                    ->where('activo', true)
                    ->firstOrFail();

                $cantidad = (float) $nuevoProducto['cantidad'];
                $precio = (float) $nuevoProducto['precio_unitario'];

                $venta->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $cantidad * $precio,
                ]);
            }

            $venta->update([
                'total' => $venta->detalles()->sum('subtotal'),
            ]);

            $venta->historial()->create([
                'user_id' => $this->usuarioIdValido(),
                'accion' => 'venta_editada',
                'valor_nuevo' => json_encode([
                    'productos_agregados' => count($datos['nuevos_productos'] ?? []),
                    'total' => $venta->total,
                ]),
            ]);
        });

        return redirect()
            ->route('ventas.show', $venta)
            ->with('success', 'Venta actualizada correctamente.');
    }

    public function anular(Venta $venta)
    {
        if ($venta->estado === 'anulada') {
            return redirect()
                ->route('ventas.show', $venta)
                ->with('success', 'La venta ya estaba anulada.');
        }

        DB::transaction(function () use ($venta) {
            $venta->update([
                'estado' => 'anulada',
            ]);

            VentaAsignacion::query()
                ->whereHas(
                    'detalle',
                    fn ($query) => $query->where('venta_id', $venta->id)
                )
                ->whereIn('estado', ['reservada', 'asignada'])
                ->update([
                    'estado' => 'liberada',
                ]);

            $venta->historial()->create([
                'user_id' => $this->usuarioIdValido(),
                'accion' => 'venta_anulada',
                'valor_anterior' => 'activa',
                'valor_nuevo' => 'anulada',
            ]);
        });

        return redirect()
            ->route('ventas.show', $venta)
            ->with('success', 'Venta anulada y unidades liberadas correctamente.');
    }

    private function ordenProducto(?Producto $producto): int
    {
        $tipo = mb_strtolower(
            trim($producto?->tipo?->nombre ?? '')
        );

        return $tipo === 'implemento' ? 0 : 1;
    }

    private function usuarioIdValido(): ?int
    {
        $usuarioId = auth()->id();

        if (
            $usuarioId &&
            DB::table('users')->where('id', $usuarioId)->exists()
        ) {
            return $usuarioId;
        }

        return null;
    }
}
