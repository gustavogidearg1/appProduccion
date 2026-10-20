<?php

namespace App\Http\Controllers;

use App\Models\AvanceProduccion;
use App\Models\EtapaProduccion;
use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\UnidadProduccion;
use App\Models\UnidadControlCalidad;

use Illuminate\Http\Request;

class AvanceProduccionController extends Controller
{
    public function unidad(
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        $this->validarRelaciones($plan, $planProducto, $unidad);

        $etapas = EtapaProduccion::query()
            ->where('alcance', 'unidad')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $avances = $unidad
            ->avances()
            ->with('etapa')
            ->get()
            ->keyBy('etapa_id');

        return view(
            'produccion.planes.avances.unidad',
            compact(
                'plan',
                'planProducto',
                'unidad',
                'etapas',
                'avances'
            )
        );
    }

    public function iniciar(
        Request $request,
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad,
        EtapaProduccion $etapa
    ) {
        $this->validarRelaciones($plan, $planProducto, $unidad);

        if ($etapa->alcance !== 'unidad' || !$etapa->activo) {
            abort(404);
        }

        $etapasAnteriores = EtapaProduccion::query()
            ->where('alcance', 'unidad')
            ->where('activo', true)
            ->where('orden', '<', $etapa->orden)
            ->orderBy('orden')
            ->get();

        foreach ($etapasAnteriores as $etapaAnterior) {
            $avanceAnterior = $unidad
                ->avances()
                ->where('etapa_id', $etapaAnterior->id)
                ->first();

            if (!$avanceAnterior || $avanceAnterior->estado !== 'completada') {
                return back()->with(
                    'error',
                    'Debe completar las etapas anteriores antes de iniciar esta etapa.'
                );
            }
        }

        $avance = AvanceProduccion::firstOrCreate(
            [
                'etapa_id' => $etapa->id,
                'unidad_produccion_id' => $unidad->id,
            ],
            [
                'estado' => 'en_proceso',
                'fecha_inicio' => now(),
                'iniciado_por' => auth()->id(),
            ]
        );

        if ($avance->estado === 'pendiente') {
            $avance->update([
                'estado' => 'en_proceso',
                'fecha_inicio' => now(),
                'iniciado_por' => auth()->id(),
            ]);

            if ($etapa->codigo === 'OK_CALIDAD') {
                $unidad->update([
                    'estado' => 'terminada',
                ]);
            }

            if ($etapa->codigo === 'ENTREGADO') {
                $unidad->update([
                    'estado' => 'entregada',
                ]);
            }
        }

        return back()->with(
            'success',
            'Etapa iniciada correctamente.'
        );
    }

    public function finalizar(
        Request $request,
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad,
        EtapaProduccion $etapa
    ) {
        $this->validarRelaciones($plan, $planProducto, $unidad);

        if ($etapa->alcance !== 'unidad' || !$etapa->activo) {
            abort(404);
        }

        $avance = $unidad
            ->avances()
            ->where('etapa_id', $etapa->id)
            ->first();

        if (!$avance) {
            return back()->with(
                'error',
                'La etapa todavía no fue iniciada.'
            );
        }

        if ($etapa->codigo === 'OK_CALIDAD') {
            $controlesPendientes = UnidadControlCalidad::query()
                ->where('unidad_produccion_id', $unidad->id)
                ->whereHas('control', function ($query) {
                    $query->where('obligatorio', true);
                })
                ->whereNotIn('resultado', [
                    'aprobado',
                    'no_aplica',
                ])
                ->count();

            if ($controlesPendientes > 0) {
                return back()->with(
                    'error',
                    'No se puede finalizar OK Calidad. Hay controles obligatorios pendientes u observados.'
                );
            }
        }

        $avance->update([
            'estado' => 'completada',
            'fecha_fin' => now(),
            'finalizado_por' => auth()->id(),
            'observaciones' => $request->input('observaciones'),
        ]);

        if ($etapa->codigo === 'OK_CALIDAD') {
    $unidad->update([
        'estado' => 'terminada',
    ]);
}

if ($etapa->codigo === 'ENTREGADO') {
    $unidad->update([
        'estado' => 'entregada',
    ]);
}

        return back()->with(
            'success',
            'Etapa finalizada correctamente.'
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

    public function plan(PlanProduccion $plan)
    {
        $etapas = EtapaProduccion::query()
            ->where('alcance', 'plan')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $avances = $plan
            ->avances()
            ->with('etapa')
            ->get()
            ->keyBy('etapa_id');

        return view(
            'produccion.planes.avances.plan',
            compact('plan', 'etapas', 'avances')
        );
    }

    public function iniciarPlan(
        PlanProduccion $plan,
        EtapaProduccion $etapa
    ) {
        if ($etapa->alcance !== 'plan' || !$etapa->activo) {
            abort(404);
        }

        $etapasAnteriores = EtapaProduccion::query()
            ->where('alcance', 'plan')
            ->where('activo', true)
            ->where('orden', '<', $etapa->orden)
            ->orderBy('orden')
            ->get();

        foreach ($etapasAnteriores as $etapaAnterior) {
            $avanceAnterior = $plan
                ->avances()
                ->where('etapa_id', $etapaAnterior->id)
                ->first();

            if (
                !$avanceAnterior ||
                $avanceAnterior->estado !== 'completada'
            ) {
                return back()->with(
                    'error',
                    'Debe completar las etapas anteriores del plan.'
                );
            }
        }

        $avance = AvanceProduccion::firstOrCreate(
            [
                'etapa_id' => $etapa->id,
                'plan_produccion_id' => $plan->id,
            ],
            [
                'estado' => 'en_proceso',
                'fecha_inicio' => now(),
                'iniciado_por' => auth()->id(),
            ]
        );

        if ($avance->estado === 'pendiente') {
            $avance->update([
                'estado' => 'en_proceso',
                'fecha_inicio' => now(),
                'iniciado_por' => auth()->id(),
            ]);
        }

        return back()->with(
            'success',
            'Etapa del plan iniciada correctamente.'
        );
    }

    public function finalizarPlan(
        Request $request,
        PlanProduccion $plan,
        EtapaProduccion $etapa
    ) {
        if ($etapa->alcance !== 'plan' || !$etapa->activo) {
            abort(404);
        }



        $avance = $plan
            ->avances()
            ->where('etapa_id', $etapa->id)
            ->first();

        if (!$avance) {
            return back()->with(
                'error',
                'La etapa todavía no fue iniciada.'
            );
        }

        $avance->update([
            'estado' => 'completada',
            'fecha_fin' => now(),
            'finalizado_por' => auth()->id(),
            'observaciones' => $request->input('observaciones'),
        ]);

        return back()->with(
            'success',
            'Etapa del plan finalizada correctamente.'
        );
    }
}
