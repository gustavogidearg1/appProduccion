<?php

namespace App\Http\Controllers;

use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $plan->load('planProductos.producto');

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
}
