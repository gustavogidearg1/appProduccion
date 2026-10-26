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
            ->whereNotIn('estado',['entregada','cancelada'])
            ->whereDoesntHave('avances',fn($q)=>$q->where('estado','completada')->whereHas('etapa',fn($e)=>$e->where('codigo','ENTREGADO')))
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
            $ventaDetalle = VentaDetalle::query()->whereKey($ventaDetalle->id)
                ->lockForUpdate()->firstOrFail();
            abort_if($ventaDetalle->venta->estado === 'anulada', 422, 'No se puede reservar en una venta anulada.');
            abort_if(
                $ventaDetalle->asignaciones()->whereIn('estado', ['reservada', 'asignada'])->count() + 1 > (float) $ventaDetalle->cantidad,
                422,
                'El detalle ya tiene todas sus unidades reservadas.'
            );
            $unidad = UnidadProduccion::query()
                ->whereKey($datos['unidad_produccion_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                (int) $unidad->planProducto?->producto_id !== (int) $ventaDetalle->producto_id,
                422,
                'El chasis no corresponde al producto de la venta.'
            );
            abort_if(in_array($unidad->estado,['entregada','cancelada']) || $unidad->avances()->where('estado','completada')->whereHas('etapa',fn($q)=>$q->where('codigo','ENTREGADO'))->exists(),422,'La unidad ya no está disponible.');
            $yaAsignada = VentaAsignacion::query()
                ->where(
                    'unidad_produccion_id',
                    $unidad->id
                )
                ->whereIn(
                    'estado',
                    ['reservada', 'asignada']
                )
                ->lockForUpdate()->first();

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
