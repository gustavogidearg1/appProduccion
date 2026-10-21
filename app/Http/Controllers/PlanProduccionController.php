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
        ]);

        return view(
            'produccion.planes.unidades',
            compact('plan', 'planProducto')
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

        $controlesCalidad = ProductoControlCalidad::query()
            ->where('producto_id', $planProducto->producto_id)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        if ($planProducto->plan_produccion_id !== $plan->id) {
            abort(404);
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

            foreach ($controlesCalidad as $controlProducto) {
                UnidadControlCalidad::create([
                    'unidad_produccion_id' => $unidad->id,
                    'control_calidad_id' => $controlProducto->control_calidad_id,
                    'resultado' => 'pendiente',
                ]);
            }
        }

        return back()->with(
            'success',
            'Unidades y componentes estándar generados correctamente.'
        );
    }
}
