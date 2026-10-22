<?php

namespace App\Http\Controllers;

use App\Models\ControlCalidad;
use App\Models\PlanProduccion;
use App\Models\PlanProducto;
use App\Models\ProductoControlCalidad;
use App\Models\UnidadControlCalidad;
use App\Models\UnidadProduccion;
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

        $productoId = $planProducto->producto_id;

        $controlesProducto = ProductoControlCalidad::query()
            ->where('producto_id', $productoId)
            ->where('activo', true)
            ->with('control')
            ->orderBy('orden')
            ->get();



        foreach ($controlesProducto as $controlProducto) {
            UnidadControlCalidad::firstOrCreate(
                [
                    'unidad_produccion_id' => $unidad->id,
                    'control_calidad_id' => $controlProducto->control_calidad_id,
                ],
                [
                    'resultado' => 'pendiente',
                ]
            );
        }

        $controles = UnidadControlCalidad::query()
            ->where('unidad_produccion_id', $unidad->id)
            ->with('control')
            ->get()
            ->sortBy(function ($controlUnidad) use ($controlesProducto) {
                return $controlesProducto
                    ->search(
                        fn($controlProducto) =>
                        $controlProducto->control_calidad_id ===
                            $controlUnidad->control_calidad_id
                    );
            });

        $planProducto->load('producto');

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

        DB::transaction(function () use ($datos, $unidad) {
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
