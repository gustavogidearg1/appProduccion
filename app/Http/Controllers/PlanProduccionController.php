<?php

namespace App\Http\Controllers;

use App\Models\EtapaProduccion;
use App\Models\FamiliaControlCalidad;
use App\Models\Familia;
use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\Producto;
use App\Models\ProductoComponente;
use App\Models\ProductoControlCalidad;
use App\Models\UnidadComponente;
use App\Models\UnidadControlCalidad;
use App\Models\UnidadProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanProduccionController extends Controller
{
    public function index(Request $request)
    {
        $etapas = EtapaProduccion::query()
            ->where('alcance', 'unidad')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $etapaIds = $etapas
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (is_string($request->input('chasis'))) $request->merge(['chasis' => trim($request->input('chasis'))]);
        $filtros = $request->validate([
            'familia_id' => ['nullable', 'integer', Rule::exists(Familia::class, 'id')],
            'producto_id' => ['nullable', 'integer', Rule::exists(Producto::class, 'id')],
            'chasis' => ['nullable', 'string', 'max:100'],
            'fecha_desde' => [
                'nullable',
                'date',
            ],

            'fecha_hasta' => [
                'nullable',
                'date',
                'after_or_equal:fecha_desde',
            ],

            'estado' => [
                'nullable',
                'string',
                'in:abierto,cerrado,cancelado',
            ],

            'etapa_id' => [
                'nullable',
                Rule::in(
                    array_merge(
                        ['planificada', 'entregada'],
                        $etapaIds
                    )
                ),
            ],
        ]);

        if (!$request->has('estado')) {
            $filtros['estado'] = 'abierto';
        }

        $consulta = PlanProduccion::with([
            'planProductos.producto',
            'planProductos.unidadesProduccion.avances.etapa',
            'planProductos.unidadesProduccion.asignaciones' => fn ($query) => $query
                ->whereIn('estado', ['reservada', 'asignada'])
                ->whereHas('detalle.venta', fn ($venta) => $venta->where('estado', '!=', 'anulada'))
                ->orderByDesc('id'),
        ]);

        if (!empty($filtros['fecha_desde'])) {
            $consulta->whereDate(
                'fecha_planificada',
                '>=',
                $filtros['fecha_desde']
            );
        }

        if (!empty($filtros['fecha_hasta'])) {
            $consulta->whereDate(
                'fecha_planificada',
                '<=',
                $filtros['fecha_hasta']
            );
        }

        if (!empty($filtros['estado'])) {
            $consulta->where(
                'estado',
                $filtros['estado']
            );
        }

        // Los productos pertenecen a cotizador: primero resolvemos sus IDs allí.
        $familias = Familia::orderBy('nombre')->get(['id','nombre']);
        $productosFiltro = Producto::orderBy('nombre')->get(['id','nombre','familia_id']);
        $filtraProducto = !empty($filtros['familia_id']) || !empty($filtros['producto_id']);
        $productoIds = $productosFiltro->filter(fn ($producto) =>
            (empty($filtros['familia_id']) || (int)$producto->familia_id === (int)$filtros['familia_id']) &&
            (empty($filtros['producto_id']) || (int)$producto->id === (int)$filtros['producto_id'])
        )->pluck('id')->all();
        $filtrarUnidad = function ($query) use ($filtros) {
            if (!empty($filtros['chasis'])) $query->where('chasis','like','%'.$filtros['chasis'].'%');
            if (!empty($filtros['etapa_id'])) {
                if ($filtros['etapa_id'] === 'planificada') {
                    $query->where('estado','planificada')->whereDoesntHave('avances');
                } elseif ($filtros['etapa_id'] === 'entregada') {
                    $query->where('estado','entregada');
                } else {
                    $query->whereHas('avances',fn ($avance) => $avance->where('etapa_id',$filtros['etapa_id'])->where('estado','en_proceso'));
                }
            }
        };
        $filtrarLinea = function ($query) use ($filtros, $filtraProducto, $productoIds, $filtrarUnidad) {
            if ($filtraProducto) $query->whereIn('producto_id',$productoIds);
            if (!empty($filtros['chasis']) || !empty($filtros['etapa_id'])) $query->whereHas('unidadesProduccion',$filtrarUnidad);
        };
        if ($filtraProducto || !empty($filtros['chasis']) || !empty($filtros['etapa_id'])) {
            $consulta->whereHas('planProductos',$filtrarLinea);
        }

        $planes = $consulta
            ->orderByRaw('fecha_planificada IS NULL')
            ->orderBy('fecha_planificada')
            ->orderBy('numero')
            ->paginate(20)
            ->withQueryString();

        $destinos = PlanProducto::whereIn('plan_produccion_id',$planes->pluck('id'))->where($filtrarLinea)->orderBy('id')->get(['id','plan_produccion_id'])->groupBy('plan_produccion_id');

        foreach ($planes as $plan) {
            $plan->setAttribute('producto_destino_id', $destinos->get($plan->id)?->first()?->id);
            $resumenPlan = ['reservadas' => 0, 'asignadas' => 0, 'total' => 0];
            foreach ($plan->planProductos as $productoPlan) {
                $resumen = ['reservadas' => 0, 'asignadas' => 0, 'total' => 0];
                foreach ($productoPlan->unidadesProduccion as $unidadPlan) {
                    // Una unidad se cuenta una sola vez, por su vínculo activo más reciente.
                    $asignacion = $unidadPlan->asignaciones->first();
                    if ($asignacion) {
                        $resumen[$asignacion->estado === 'reservada' ? 'reservadas' : 'asignadas']++;
                        $resumen['total']++;
                    }
                }
                $productoPlan->setAttribute('resumen_ventas', $resumen);
                foreach ($resumen as $clave => $cantidad) {
                    $resumenPlan[$clave] += $cantidad;
                }
            }
            $plan->setAttribute('resumen_ventas', $resumenPlan);
        }

        return view(
            'produccion.planes.index',
            compact('planes', 'filtros', 'etapas', 'familias', 'productosFiltro')
        );
    }

    public function create()
    {
        $productos = Producto::query()
            ->where('activo', true)
            ->with(['familia','tipo'])
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
                'ue_produccion',
                'familia_id',
                'tipo_id',
            ]);

        return view(
            'produccion.planes.create',
            compact('productos')
        );
    }

    public function show(PlanProduccion $plan)
    {
        $planProducto = $plan->planProductos()->orderBy('id')->first();
        if (!$planProducto) {
            return redirect()->route('planes.index')
                ->with('error', 'El plan no tiene productos cargados.');
        }

        return redirect()->route('planes.productos.unidades', [$plan, $planProducto]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'numero' => [
                'required',
                'integer',
                'min:1',
                'unique:planes_produccion,numero',
            ],

            'fecha_planificada' => [
                'nullable',
                'date',
            ],

            'fecha_fin_planificada' => [
                'nullable',
                'date',
                'after_or_equal:fecha_planificada',
            ],

            'prioridad' => [
                'nullable',
                'integer',
                'min:0',
                'max:255',
            ],

            'observaciones_plan' => [
                'nullable',
                'string',
            ],

            'producto_id' => [
                'required',
                'array',
                'min:1',
            ],

            'producto_id.*' => [
                'required',
                'integer',
                'distinct',
            ],

            'cantidad' => [
                'required',
                'array',
                'min:1',
            ],

            'cantidad.*' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'observaciones' => [
                'nullable',
                'array',
            ],

            'observaciones.*' => [
                'nullable',
                'string',
            ],
        ]);

        $productosActivos = Producto::query()
            ->whereIn('id', $datos['producto_id'])
            ->where('activo', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $productosSolicitados = array_map(
            fn ($id) => (int) $id,
            $datos['producto_id']
        );

        if (count($productosActivos) !== count($productosSolicitados)) {
            throw ValidationException::withMessages([
                'producto_id' =>
                    'Uno o más productos no existen o están inactivos.',
            ]);
        }

        DB::transaction(function () use ($datos) {
            $plan = PlanProduccion::create([
                'numero' => $datos['numero'],
                'estado' => 'abierto',
                'fecha_planificada' =>
                    $datos['fecha_planificada'] ?? null,
                'fecha_fin_planificada' =>
                    $datos['fecha_fin_planificada'] ?? null,
                'prioridad' => $datos['prioridad'] ?? 0,
                'observaciones' =>
                    $datos['observaciones_plan'] ?? null,
            ]);

            foreach ($datos['producto_id'] as $indice => $productoId) {
                PlanProducto::create([
                    'plan_produccion_id' => $plan->id,
                    'producto_id' => $productoId,
                    'cantidad' => $datos['cantidad'][$indice],
                    'observaciones' =>
                        $datos['observaciones'][$indice] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('planes.index')
            ->with(
                'success',
                'Plan de producción creado correctamente.'
            );
    }

    public function unidades(
        PlanProduccion $plan,
        PlanProducto $planProducto
    ) {
        if ($planProducto->plan_produccion_id !== $plan->id) {
            abort(404);
        }

        $plan->load(['planProductos' => fn ($query) => $query->orderBy('id')->with('producto')->withCount('unidadesProduccion')]);

        $planProducto->load([
            'producto',
            'unidadesProduccion.cliente',
            'unidadesProduccion.asignaciones' => fn ($query) => $query
                ->whereIn('estado', ['reservada', 'asignada'])
                ->whereHas('detalle.venta', fn ($venta) => $venta->where('estado', '!=', 'anulada'))
                ->with('detalle.venta.cliente')
                ->orderByDesc('id'),
            'unidadesProduccion.componentes',
            'unidadesProduccion.controlesCalidad',
            'unidadesProduccion.avances.etapa',
        ]);

        $plan->loadMissing('avances.etapa');
        $planProducto->setRelation('plan', $plan);
        $estimador = new \App\Services\EstimadorEntrega();
        foreach ($planProducto->unidadesProduccion as $unidadPlan) {
            $unidadPlan->setRelation('planProducto', $planProducto);
            $unidadPlan->setAttribute('estimacion_entrega', $estimador->calcular($unidadPlan));
        }

        $controlesFamiliaIds = FamiliaControlCalidad::query()
            ->where(
                'familia_id',
                $planProducto->producto?->familia_id
            )
            ->where('activo', true)
            ->pluck('control_calidad_id');

        return view(
            'produccion.planes.unidades',
            compact(
                'plan',
                'planProducto',
                'controlesFamiliaIds'
            )
        );
    }

    public function showUnidad(
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        if (
            $planProducto->plan_produccion_id !== $plan->id ||
            $unidad->plan_producto_id !== $planProducto->id
        ) {
            abort(404);
        }

        $unidad->load([
            'planProducto.producto',
            'cliente',
            'componentes.producto',
            'controlesCalidad.control',
            'avances.etapa',
        ]);

        $asignacionVenta = $unidad->asignaciones()
            ->whereIn('estado', ['reservada', 'asignada'])
            ->whereHas('detalle.venta', fn ($query) => $query->where('estado', '!=', 'anulada'))
            ->with([
                'detalle.producto',
                'detalle.venta.cliente',
                'detalle.venta.detalles.producto.tipo',
            ])
            ->orderByDesc('id')
            ->first();
        $ventaVinculada = $asignacionVenta?->detalle?->venta;
        $otrosDetallesVenta = $ventaVinculada
            ? $ventaVinculada->detalles->reject(fn ($detalle) => $detalle->id === $asignacionVenta->venta_detalle_id)
            : collect();

        $unidad->setAttribute('estimacion_entrega', (new \App\Services\EstimadorEntrega())->calcular($unidad));

        return view(
            'produccion.planes.unidad-show',
            compact(
                'plan',
                'planProducto',
                'unidad',
                'asignacionVenta',
                'ventaVinculada',
                'otrosDetallesVenta'
            )
        );
    }

    public function editUnidad(
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        if (
            $planProducto->plan_produccion_id !== $plan->id ||
            $unidad->plan_producto_id !== $planProducto->id
        ) {
            abort(404);
        }

        $colores = DB::connection('cotizador')
            ->table('colores')
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
                'descripcion',
            ]);

        return view(
            'produccion.planes.unidad-edit',
            compact(
                'plan',
                'planProducto',
                'unidad',
                'colores'
            )
        );
    }

    public function updateUnidad(
        Request $request,
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificarlo.');

        if (
            $planProducto->plan_produccion_id !== $plan->id ||
            $unidad->plan_producto_id !== $planProducto->id
        ) {
            abort(404);
        }

        if ($unidad->estado === 'entregada') {
            return back()->with(
                'error',
                'No se puede modificar una unidad que ya fue entregada.'
            );
        }

        $datos = $request->validate([
            'chasis' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique(
                    'unidades_produccion',
                    'chasis'
                )->ignore($unidad->id),
            ],

            'serie' => ['sometimes', 'nullable', 'string', 'max:100'],

            'estado' => [
                'required',
                'string',
                'in:planificada,en_produccion,terminada,entregada,cancelada',
            ],

            'color' => [
                'nullable',
                'string',
                'max:100',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],
        ]);

        abort_if($datos['estado'] === 'entregada',422,'Registrá la entrega desde el control de salida de la unidad.');

        $unidad->update($datos);

        return redirect()
            ->route(
                'planes.productos.unidades',
                [$plan, $planProducto]
            )
            ->with(
                'success',
                'Unidad actualizada correctamente.'
            );
    }

    public function generarUnidades(
        PlanProduccion $plan,
        PlanProducto $planProducto
    ) {
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificarlo.');

        if ($planProducto->plan_produccion_id !== $plan->id) {
            abort(404);
        }

        $planProducto->load('producto');

        $familiaId = $planProducto->producto?->familia_id;

        $controlesFamilia = FamiliaControlCalidad::query()
            ->where('familia_id', $familiaId)
            ->where('activo', true)
            ->orderBy('etapa_produccion_id')
            ->orderBy('orden')
            ->get();

        $controlesProducto = collect();

        if ($controlesFamilia->isEmpty()) {
            $controlesProducto = ProductoControlCalidad::query()
                ->where(
                    'producto_id',
                    $planProducto->producto_id
                )
                ->where('activo', true)
                ->orderBy('orden')
                ->get();
        }

        $cantidad = (float) $planProducto->cantidad;

        if (
            $cantidad <= 0 ||
            floor($cantidad) !== $cantidad
        ) {
            return back()->with(
                'error',
                'La cantidad debe ser un número entero para generar unidades.'
            );
        }

        $componentesEstandar = ProductoComponente::query()
            ->where(
                'producto_id',
                $planProducto->producto_id
            )
            ->where('activo', true)
            ->get();

        $existentes = $planProducto
            ->unidadesProduccion()
            ->count();

        for (
            $numero = $existentes + 1;
            $numero <= (int) $cantidad;
            $numero++
        ) {
            $unidad = UnidadProduccion::create([
                'plan_producto_id' => $planProducto->id,
                'numero_unidad' => $numero,
                'estado' => 'planificada',
            ]);

            foreach ($componentesEstandar as $componenteEstandar) {
                UnidadComponente::create([
                    'unidad_produccion_id' => $unidad->id,
                    'producto_id' =>
                        $componenteEstandar->componente_producto_id,
                    'cantidad' => $componenteEstandar->cantidad,
                    'estado' => 'pendiente',
                    'uso' => $componenteEstandar->uso,
                    'observaciones' =>
                        $componenteEstandar->observaciones,
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($controlesFamilia as $controlFamilia) {
                UnidadControlCalidad::create([
                    'unidad_produccion_id' => $unidad->id,
                    'control_calidad_id' =>
                        $controlFamilia->control_calidad_id,
                    'etapa_produccion_id' =>
                        $controlFamilia->etapa_produccion_id,
                    'resultado' => 'pendiente',
                ]);
            }

            foreach ($controlesProducto as $controlProducto) {
                UnidadControlCalidad::create([
                    'unidad_produccion_id' => $unidad->id,
                    'control_calidad_id' =>
                        $controlProducto->control_calidad_id,
                    'resultado' => 'pendiente',
                ]);
            }
        }

        return back()->with(
            'success',
            'Unidades, componentes y controles de calidad generados correctamente.'
        );
    }
}
