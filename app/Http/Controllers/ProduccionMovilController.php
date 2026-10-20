<?php

namespace App\Http\Controllers;

use App\Models\PlanProduccion;
use App\Models\UnidadProduccion;
use Illuminate\Http\Request;

class ProduccionMovilController extends Controller
{
    public function index(Request $request)
    {
        $plan = null;
        $unidad = null;
        $etapaActual = null;
        $mensaje = null;

        if ($request->filled('plan') || $request->filled('chasis')) {
            $datos = $request->validate([
                'plan' => [
                    'required',
                    'integer',
                ],

                'chasis' => [
                    'required',
                    'string',
                    'max:100',
                ],
            ]);

            $plan = PlanProduccion::query()
                ->where('numero', $datos['plan'])
                ->first();

            if (!$plan) {
                $mensaje = 'No se encontró el plan indicado.';
            } else {
                $unidad = UnidadProduccion::query()
                    ->with([
                        'planProducto.producto',
                        'avances.etapa',
                    ])
                    ->where('chasis', $datos['chasis'])
                    ->whereHas('planProducto', function ($query) use ($plan) {
                        $query->where(
                            'plan_produccion_id',
                            $plan->id
                        );
                    })
                    ->first();

                if (!$unidad) {
                    $mensaje = 'No se encontró una unidad con ese chasis dentro del plan.';
                } else {
                    $etapaActual = $unidad
                        ->avances
                        ->sortBy('etapa.orden')
                        ->first(function ($avance) {
                            return $avance->estado !== 'completada';
                        });

                    if (!$etapaActual) {
                        $etapaActual = $unidad
                            ->avances
                            ->sortByDesc('etapa.orden')
                            ->first();
                    }
                }
            }
        }

        return view(
            'produccion.movil.index',
            compact(
                'plan',
                'unidad',
                'etapaActual',
                'mensaje'
            )
        );
    }
}
