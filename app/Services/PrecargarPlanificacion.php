<?php

namespace App\Services;

use App\Models\EtapaProduccion;
use App\Models\PlanProduccion;
use Illuminate\Support\Facades\DB;

class PrecargarPlanificacion
{
    public static function crear(PlanProduccion $plan): void
    {
        if (!$plan->fecha_planificada) return;
        $productos = $plan->planProductos()->pluck('producto_id')->unique();
        // Las etapas son globales: solo precargar si todos los productos coinciden.
        $etapas = SectoresProduccion::ordenar(EtapaProduccion::where('activo', true)->where('alcance', 'plan')->get());
        $reglas = DB::table('producto_plazos_entrega')->whereIn('producto_id', $productos)->where('activo', true)->get()->groupBy('producto_id');
        $perfiles = $productos->map(function ($id) use ($reglas, $etapas) {
            $porEtapa = ($reglas->get($id) ?? collect())->keyBy('etapa_id');
            return $etapas->mapWithKeys(fn ($etapa) => [$etapa->id => isset($porEtapa[$etapa->id]) ? (int) $porEtapa[$etapa->id]->dias_habiles : null])->all();
        });
        if ($perfiles->isEmpty() || $perfiles->map(fn ($p) => json_encode($p))->unique()->count() !== 1) return;
        $dias = $perfiles->first();
        $inicial = $etapas->firstWhere('codigo', 'PLANIFICADO');
        $total = $inicial ? ($dias[$inicial->id] ?? null) : null;
        if ($total === null) return;
        $calendario = new EstimadorEntrega();
        $inicios = [];
        foreach ($etapas as $etapa) {
            $restantes = $dias[$etapa->id] ?? null;
            if ($restantes === null || $restantes > $total) continue;
            $inicios[$etapa->id] = $calendario->sumarHabiles($plan->fecha_planificada, $total - $restantes);
        }
        foreach ($etapas as $i => $etapa) {
            if (!isset($inicios[$etapa->id])) continue;
            $siguiente = $etapas->get($i + 1);
            $fin = $siguiente ? ($inicios[$siguiente->id] ?? null) : null;
            // Las etapas paralelas comparten inicio; terminan al próximo inicio posterior.
            if ($fin && $fin->equalTo($inicios[$etapa->id]) && !in_array($etapa->codigo, ['PLANIFICADO', 'LANZADO'], true)) {
                $fin = collect($inicios)->filter(fn ($f) => $f->greaterThan($inicios[$etapa->id]))->sort()->first();
            }
            if ($fin && $fin->lessThan($inicios[$etapa->id])) $fin = null;
            $plan->avances()->firstOrCreate([
                'etapa_id' => $etapa->id,
                'unidad_produccion_id' => null,
            ], [
                'estado' => 'pendiente',
                'fecha_inicio_planificada' => $inicios[$etapa->id]->toDateString(),
                'fecha_fin_planificada' => $fin?->toDateString(),
            ]);
        }
    }
}
