<?php

namespace App\Http\Controllers;

use App\Models\UnidadProduccion;
use App\Models\VentaAsignacion;
use App\Models\VentaDetalle;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaAsignacionController extends Controller
{
    public function create(VentaDetalle $ventaDetalle)
    {
        $ventaDetalle->load([
            'venta.cliente',
            'producto.familia',
        ]);

        $unidades = UnidadProduccion::query()
            ->whereHas(
                'planProducto',
                function ($query) use ($ventaDetalle) {
                    $query->where(
                        'producto_id',
                        $ventaDetalle->producto_id
                    );
                }
            )
            ->with([
                'planProducto.producto',
                'avances.etapa',
                'asignaciones',
            ])
            ->whereDoesntHave(
                'asignaciones',
                function ($query) {
                    $query->whereIn(
                        'estado',
                        ['reservada', 'asignada']
                    );
                }
            )
            ->orderBy('estado')
            ->orderBy('chasis')
            ->get();

        return view(
            'ventas.asignar',
            compact('ventaDetalle', 'unidades')
        );
    }

    public function store(
        Request $request,
        VentaDetalle $ventaDetalle
    ) {
        $datos = $request->validate([
            'unidad_produccion_id' => [
                'required',
                'integer',
                'exists:unidades_produccion,id',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],
        ]);

        $usuarioId = auth()->id();

        if (
            $usuarioId &&
            !DB::table('users')
                ->where('id', $usuarioId)
                ->exists()
        ) {
            $usuarioId = null;
        }
        DB::transaction(function () use (
            $datos,
            $ventaDetalle,
            $usuarioId
        ) {
            $unidad = UnidadProduccion::query()
                ->whereKey($datos['unidad_produccion_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $yaAsignada = VentaAsignacion::query()
                ->where(
                    'unidad_produccion_id',
                    $unidad->id
                )
                ->whereIn(
                    'estado',
                    ['reservada', 'asignada']
                )
                ->exists();

            if ($yaAsignada) {
                abort(
                    422,
                    'La unidad ya fue asignada a otra venta.'
                );
            }

            VentaAsignacion::create([
                'venta_detalle_id' => $ventaDetalle->id,
                'unidad_produccion_id' => $unidad->id,
                'estado' => 'reservada',
                'fecha_reserva' => now(),
                'user_id' => $usuarioId,
                'observaciones' =>
                $datos['observaciones'] ?? null,
            ]);

            $ventaDetalle->venta->historial()->create([
                'user_id' => $usuarioId,
                'accion' => 'unidad_asignada',
                'datos' => [
                    'venta_detalle_id' => $ventaDetalle->id,
                    'unidad_produccion_id' => $unidad->id,
                    'chasis' => $unidad->chasis,
                ],
            ]);
        });

        return redirect()
            ->route(
                'ventas.show',
                $ventaDetalle->venta
            )
            ->with(
                'success',
                'Unidad reservada correctamente.'
            );
    }
}
