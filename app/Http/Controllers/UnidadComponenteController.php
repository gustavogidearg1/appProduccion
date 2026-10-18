<?php

namespace App\Http\Controllers;

use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\Producto;
use App\Models\UnidadComponente;
use App\Models\UnidadProduccion;
use Illuminate\Http\Request;

class UnidadComponenteController extends Controller
{
    public function index(
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        $this->validarRelaciones($plan, $planProducto, $unidad);

        $unidad->load([
            'planProducto.producto',
            'componentes.producto',
        ]);

        return view(
            'produccion.planes.componentes.index',
            compact('plan', 'planProducto', 'unidad')
        );
    }

    public function create(
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        $this->validarRelaciones($plan, $planProducto, $unidad);

        $productos = Producto::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
                'ue_produccion',
            ]);

        return view(
            'produccion.planes.componentes.create',
            compact(
                'plan',
                'planProducto',
                'unidad',
                'productos'
            )
        );
    }

    public function store(
        Request $request,
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        $this->validarRelaciones($plan, $planProducto, $unidad);

        $datos = $request->validate([
            'producto_id' => [
                'required',
                'integer',
            ],

            'cantidad' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'estado' => [
                'required',
                'in:pendiente,instalado,no corresponde,retirado',
            ],

            'uso' => [
                'required',
                'in:definitivo,exhibicion',
            ],

            'trazabilidad' => [
                'nullable',
                'string',
                'max:150',
            ],

            'fecha_instalacion' => [
                'nullable',
                'date',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],
        ]);

        Producto::query()
            ->where('activo', true)
            ->findOrFail($datos['producto_id']);

        UnidadComponente::create([
            'unidad_produccion_id' => $unidad->id,
            'producto_id' => $datos['producto_id'],
            'cantidad' => $datos['cantidad'],
            'estado' => $datos['estado'],
            'uso' => $datos['uso'],
            'trazabilidad' => $datos['trazabilidad'] ?? null,
            'fecha_instalacion' => $datos['fecha_instalacion'] ?? null,
            'user_id' => auth()->id(),
            'observaciones' => $datos['observaciones'] ?? null,
        ]);

        return redirect()
            ->route(
                'planes.productos.unidades.componentes.index',
                [$plan, $planProducto, $unidad]
            )
            ->with(
                'success',
                'Componente agregado correctamente.'
            );
    }

    public function edit(
    PlanProduccion $plan,
    PlanProducto $planProducto,
    UnidadProduccion $unidad,
    UnidadComponente $componente
) {
    $this->validarRelaciones($plan, $planProducto, $unidad);

    if ($componente->unidad_produccion_id !== $unidad->id) {
        abort(404);
    }

    $componente->load('producto');

    return view(
        'produccion.planes.componentes.edit',
        compact(
            'plan',
            'planProducto',
            'unidad',
            'componente'
        )
    );
}

public function update(
    Request $request,
    PlanProduccion $plan,
    PlanProducto $planProducto,
    UnidadProduccion $unidad,
    UnidadComponente $componente
) {
    $this->validarRelaciones($plan, $planProducto, $unidad);

    if ($componente->unidad_produccion_id !== $unidad->id) {
        abort(404);
    }

    $datos = $request->validate([
        'cantidad' => [
            'required',
            'numeric',
            'min:0.01',
        ],

        'estado' => [
            'required',
            'in:pendiente,instalado,no corresponde,retirado',
        ],

        'uso' => [
            'required',
            'in:definitivo,exhibicion',
        ],

        'trazabilidad' => [
            'nullable',
            'string',
            'max:150',
        ],

        'fecha_instalacion' => [
            'nullable',
            'date',
        ],

        'observaciones' => [
            'nullable',
            'string',
        ],
    ]);

    if (
        $datos['estado'] === 'instalado' &&
        empty($datos['fecha_instalacion'])
    ) {
        return back()
            ->withErrors([
                'fecha_instalacion' =>
                    'La fecha es obligatoria cuando el componente está instalado.',
            ])
            ->withInput();
    }

    $componente->update($datos);

    return redirect()
        ->route(
            'planes.productos.unidades.componentes.index',
            [$plan, $planProducto, $unidad]
        )
        ->with(
            'success',
            'Componente actualizado correctamente.'
        );
}

    private function validarRelaciones(
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ): void {
        if (
            $planProducto->plan_produccion_id !== $plan->id ||
            $unidad->plan_producto_id !== $planProducto->id
        ) {
            abort(404);
        }
    }
}
