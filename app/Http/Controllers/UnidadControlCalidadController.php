<?php

namespace App\Http\Controllers;

use App\Models\ControlCalidad;
use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\ProductoControlCalidad;
use App\Models\UnidadControlCalidad;
use App\Models\UnidadProduccion;
use App\Models\FamiliaControlCalidad;
use App\Models\AvanceProduccion;
use App\Models\EtapaProduccion;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class UnidadControlCalidadController extends Controller
{
    public function index(
    PlanProduccion $plan,
    PlanProducto $planProducto,
    UnidadProduccion $unidad
) {
    $this->validarRelaciones($plan, $planProducto, $unidad);

    $planProducto->load('producto');

    $familiaId = $planProducto->producto?->familia_id;

    $controlesFamilia = FamiliaControlCalidad::query()
        ->where('familia_id', $familiaId)
        ->where('activo', true)
        ->with(['control', 'etapa'])
        ->orderBy('etapa_produccion_id')
        ->orderBy('orden')
        ->get();

    foreach ($controlesFamilia as $controlFamilia) {
        UnidadControlCalidad::firstOrCreate(
            [
                'unidad_produccion_id' => $unidad->id,
                'control_calidad_id' => $controlFamilia->control_calidad_id,
            ],
            [
                'etapa_produccion_id' => $controlFamilia->etapa_produccion_id,
                'resultado' => 'pendiente',
            ]
        );
    }

    $controles = UnidadControlCalidad::query()
        ->where('unidad_produccion_id', $unidad->id)
        ->whereIn(
            'control_calidad_id',
            $controlesFamilia->pluck('control_calidad_id')
        )
        ->with(['control', 'etapa'])
        ->get()
        ->sortBy(function ($controlUnidad) use ($controlesFamilia) {
            return $controlesFamilia->search(
                fn ($controlFamilia) =>
                    $controlFamilia->control_calidad_id ===
                    $controlUnidad->control_calidad_id
            );
        })
        ->values();

    return view(
        'produccion.planes.calidad.unidad',
        compact(
            'plan',
            'planProducto',
            'unidad',
            'controles'
        )
    );
}

    public function update(
        Request $request,
        PlanProduccion $plan,
        PlanProducto $planProducto,
        UnidadProduccion $unidad
    ) {
        $this->validarRelaciones($plan, $planProducto, $unidad);

        $planProducto->load('producto');

        $datos = $request->validate([
            'controles' => [
                'required',
                'array',
            ],

            'controles.*.evidencia' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'controles.*.resultado' => [
                'required',
                Rule::in([
                    'pendiente',
                    'aprobado',
                    'observado',
                    'no_aplica',
                ]),
            ],

            'controles.*.valor' => [
                'nullable',
                'string',
            ],

            'controles.*.observaciones' => [
                'nullable',
                'string',
            ],
        ]);

        DB::transaction(function () use (
    $datos,
    $unidad,
    $planProducto
) {
    foreach ($datos['controles'] as $controlId => $control) {
        $controlUnidad = UnidadControlCalidad::query()
            ->where('id', $controlId)
            ->where('unidad_produccion_id', $unidad->id)
            ->first();

        if (!$controlUnidad) {
            continue;
        }

        $datosActualizar = [
            'resultado' => $control['resultado'],
            'valor' => $control['valor'] ?? null,
            'observaciones' => $control['observaciones'] ?? null,
            'user_id' => auth()->id(),
            'fecha_control' => now(),
        ];

        if (
            isset($control['evidencia']) &&
            $control['evidencia'] instanceof \Illuminate\Http\UploadedFile
        ) {
            if ($controlUnidad->evidencia_path) {
                Storage::disk('public')->delete(
                    $controlUnidad->evidencia_path
                );
            }

            $datosActualizar['evidencia_path'] =
                $control['evidencia']->store(
                    'calidad/evidencias',
                    'public'
                );
        }

        $controlUnidad->update($datosActualizar);
    }

    $this->avanzarEtapaAutomaticamente(
        $unidad,
        $planProducto
    );
});

return redirect()
    ->route(
        'planes.productos.unidades.calidad',
        [$plan, $planProducto, $unidad]
    )
    ->with(
        'success',
        'Control de calidad guardado correctamente.'
    );
}

private function avanzarEtapaAutomaticamente(
    UnidadProduccion $unidad,
    PlanProducto $planProducto
): void {
    $avanceActual = $unidad
        ->avances()
        ->with('etapa')
        ->where('estado', 'en_proceso')
        ->first();

    if (!$avanceActual) {
        return;
    }

    $familiaId = $planProducto->producto?->familia_id;

    $controlesEtapa = FamiliaControlCalidad::query()
        ->where('familia_id', $familiaId)
        ->where('etapa_produccion_id', $avanceActual->etapa_id)
        ->where('activo', true)
        ->get();

    if ($controlesEtapa->isEmpty()) {
        return;
    }

    $controlesObligatorios = $controlesEtapa
        ->where('obligatorio', true)
        ->pluck('control_calidad_id');

    $pendientes = UnidadControlCalidad::query()
        ->where('unidad_produccion_id', $unidad->id)
        ->whereIn('control_calidad_id', $controlesObligatorios)
        ->where('resultado', 'pendiente')
        ->exists();

    if ($pendientes) {
        return;
    }

    $avanceActual->update([
        'estado' => 'completada',
        'fecha_fin' => now(),
        'finalizado_por' => auth()->id(),
    ]);

    $siguienteEtapa = EtapaProduccion::query()
        ->where('alcance', 'unidad')
        ->where('activo', true)
        ->where('orden', '>', $avanceActual->etapa->orden)
        ->orderBy('orden')
        ->first();

    if (!$siguienteEtapa) {
        return;
    }

    $siguienteAvance = AvanceProduccion::firstOrCreate(
        [
            'unidad_produccion_id' => $unidad->id,
            'etapa_id' => $siguienteEtapa->id,
        ],
        [
            'estado' => 'en_proceso',
            'fecha_inicio' => now(),
            'iniciado_por' => auth()->id(),
        ]
    );

    if ($siguienteAvance->estado === 'pendiente') {
        $siguienteAvance->update([
            'estado' => 'en_proceso',
            'fecha_inicio' => now(),
            'iniciado_por' => auth()->id(),
        ]);
    }
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
