<?php

namespace App\Http\Controllers;

use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\Producto;
use App\Models\ProductoComponente;
use App\Models\UnidadProduccion;
use App\Models\UnidadComponente;
use App\Models\ProductoControlCalidad;
use App\Models\UnidadControlCalidad;
use App\Models\FamiliaControlCalidad;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class PlanProduccionController extends Controller
{
    public function index()
    {
        $planes = PlanProduccion::with('planProductos.producto')
            ->orderByDesc('numero')
            ->paginate(20);

        return view('produccion.planes.index', compact('planes'));
    }

    public function create()
    {
        $productos = Producto::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
                'ue_produccion',
            ]);

        return view('produccion.planes.create', compact('productos'));
    }

    public function show(PlanProduccion $plan)
    {
        $plan->load([
            'planProductos.producto',
            'planProductos.unidadesProduccion',
        ]);

        return view('produccion.planes.show', compact('plan'));
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
            ->map(fn($id) => (int) $id)
            ->all();

        $productosSolicitados = array_map(
            fn($id) => (int) $id,
            $datos['producto_id']
        );

        if (count($productosActivos) !== count($productosSolicitados)) {
            throw ValidationException::withMessages([
                'producto_id' => 'Uno o más productos no existen o están inactivos.',
            ]);
        }

        DB::transaction(function () use ($datos) {
            $plan = PlanProduccion::create([
                'numero' => $datos['numero'],
                'estado' => 'abierto',
                'fecha_planificada' => $datos['fecha_planificada'] ?? null,
                'prioridad' => $datos['prioridad'] ?? 0,
                'observaciones' => $datos['observaciones_plan'] ?? null,
            ]);

            foreach ($datos['producto_id'] as $indice => $productoId) {
                PlanProducto::create([
                    'plan_produccion_id' => $plan->id,
                    'producto_id' => $productoId,
                    'cantidad' => $datos['cantidad'][$indice],
                    'observaciones' => $datos['observaciones'][$indice] ?? null,
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

    $planProducto->load([
        'producto',
        'unidadesProduccion.cliente',
        'unidadesProduccion.componentes',
        'unidadesProduccion.controlesCalidad',
        'unidadesProduccion.avances.etapa',
    ]);

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

        return view(
            'produccion.planes.unidad-show',
            compact(
                'plan',
                'planProducto',
                'unidad'
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
                Rule::unique('unidades_produccion', 'chasis')
                    ->ignore($unidad->id),
            ],

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


        $unidad->update($datos);

        return redirect()
            ->route('planes.productos.unidades', [$plan, $planProducto])
            ->with('success', 'Unidad actualizada correctamente.');
    }

    public function generarUnidades(
        PlanProduccion $plan,
        PlanProducto $planProducto
    ) {
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

        /*
     * Compatibilidad temporal:
     * si la familia todavía no tiene controles,
     * utiliza los controles antiguos del producto.
     */
        $controlesProducto = collect();

        if ($controlesFamilia->isEmpty()) {
            $controlesProducto = ProductoControlCalidad::query()
                ->where('producto_id', $planProducto->producto_id)
                ->where('activo', true)
                ->orderBy('orden')
                ->get();
        }

        $cantidad = (float) $planProducto->cantidad;

        if ($cantidad <= 0 || floor($cantidad) !== $cantidad) {
            return back()->with(
                'error',
                'La cantidad debe ser un número entero para generar unidades.'
            );
        }

        $componentesEstandar = ProductoComponente::query()
            ->where('producto_id', $planProducto->producto_id)
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
                    'producto_id' => $componenteEstandar->componente_producto_id,
                    'cantidad' => $componenteEstandar->cantidad,
                    'estado' => 'pendiente',
                    'uso' => $componenteEstandar->uso,
                    'observaciones' => $componenteEstandar->observaciones,
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($controlesFamilia as $controlFamilia) {
                UnidadControlCalidad::create([
                    'unidad_produccion_id' => $unidad->id,
                    'control_calidad_id' => $controlFamilia->control_calidad_id,
                    'etapa_produccion_id' => $controlFamilia->etapa_produccion_id,
                    'resultado' => 'pendiente',
                ]);
            }

            foreach ($controlesProducto as $controlProducto) {
                UnidadControlCalidad::create([
                    'unidad_produccion_id' => $unidad->id,
                    'control_calidad_id' => $controlProducto->control_calidad_id,
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
