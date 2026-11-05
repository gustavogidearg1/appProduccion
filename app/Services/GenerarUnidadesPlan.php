<?php
namespace App\Services;
use App\Models\{PlanProduccion,PlanProducto,UnidadProduccion,ProductoComponente,UnidadComponente,FamiliaControlCalidad,ProductoControlCalidad,UnidadControlCalidad};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class GenerarUnidadesPlan {
 public static function generar(PlanProduccion $plan,PlanProducto $planProducto,?string $inicio=null,?int $cantidadNueva=null): void {
  try {DB::transaction(function()use($plan,$planProducto,$inicio,$cantidadNueva){
   $plan=PlanProduccion::whereKey($plan->id)->lockForUpdate()->firstOrFail();
   $planProducto=PlanProducto::whereKey($planProducto->id)->lockForUpdate()->firstOrFail();
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificarlo.');

        if ($planProducto->plan_produccion_id !== $plan->id) {
            abort(404);
        }

        $planProducto->load('producto');

        $familiaId = $planProducto->producto?->familia_id;

        $controlesFamilia = FamiliaControlCalidad::query()
            ->where('familia_id', $familiaId)
            ->where('activo', true)
            ->orderBy('etapa_produccion_id')
            ->orderBy('orden')
            ->get();

        $controlesProducto = collect();

        if ($controlesFamilia->isEmpty()) {
            $controlesProducto = ProductoControlCalidad::query()
                ->where(
                    'producto_id',
                    $planProducto->producto_id
                )
                ->where('activo', true)
                ->orderBy('orden')
                ->get();
        }

        $cantidad = (float) ($cantidadNueva ?? $planProducto->cantidad);

        if ($cantidad<=0 || floor($cantidad)!==$cantidad || $cantidad>10000)throw ValidationException::withMessages(['cantidad'=>'Ingresá una cantidad entera de 1 a 10000 unidades.']);
        if($cantidadNueva!==null && $planProducto->unidadesProduccion()->exists())throw ValidationException::withMessages(['cantidad'=>'La cantidad solo puede corregirse antes de generar las unidades.']);
        $numerosExistentes=$planProducto->unidadesProduccion()->pluck('numero_unidad')->all();
        $numeros=array_values(array_diff(range(1,(int)$cantidad),$numerosExistentes));$chasis=[];
        if($inicio!==null && $inicio!==''){
            if(!preg_match('/^[0-9]{1,12}$/',$inicio) || (int)$inicio<1 || (int)$inicio+(int)$cantidad-1>999999999999)throw ValidationException::withMessages(['chasis_inicial'=>'Ingresá un chasis inicial numérico válido, de hasta 12 dígitos.']);
            foreach($numeros as $n)$chasis[$n]=str_pad((string)((int)$inicio+$n-1),strlen($inicio),'0',STR_PAD_LEFT);
            $ocupados=UnidadProduccion::whereIn('chasis',array_values($chasis))->pluck('chasis');
            if($ocupados->isNotEmpty())throw ValidationException::withMessages(['chasis_inicial'=>'No se generaron unidades. Ya están utilizados estos chasis: '.$ocupados->take(20)->implode(', ').'.']);
        }
        if($cantidadNueva!==null)$planProducto->update(['cantidad'=>(int)$cantidad]);
        $componentesEstandar = ProductoComponente::query()
            ->where(
                'producto_id',
                $planProducto->producto_id
            )
            ->where('activo', true)
            ->get();

        foreach($numeros as $numero) {
            $unidad = UnidadProduccion::create([
                'plan_producto_id' => $planProducto->id,
                'numero_unidad' => $numero,
                'chasis' => $chasis[$numero] ?? null,
                'estado' => 'planificada',
            ]);

            foreach ($componentesEstandar as $componenteEstandar) {
                UnidadComponente::create([
                    'unidad_produccion_id' => $unidad->id,
                    'producto_id' =>
                        $componenteEstandar->componente_producto_id,
                    'cantidad' => $componenteEstandar->cantidad,
                    'estado' => 'pendiente',
                    'uso' => $componenteEstandar->uso,
                    'observaciones' =>
                        $componenteEstandar->observaciones,
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($controlesFamilia as $controlFamilia) {
                UnidadControlCalidad::create([
                    'unidad_produccion_id' => $unidad->id,
                    'control_calidad_id' =>
                        $controlFamilia->control_calidad_id,
                    'etapa_produccion_id' =>
                        $controlFamilia->etapa_produccion_id,
                    'resultado' => 'pendiente',
                ]);
            }

            foreach ($controlesProducto as $controlProducto) {
                UnidadControlCalidad::create([
                    'unidad_produccion_id' => $unidad->id,
                    'control_calidad_id' =>
                        $controlProducto->control_calidad_id,
                    'resultado' => 'pendiente',
                ]);
            }
        }

  });}catch(\Illuminate\Database\UniqueConstraintViolationException $e){if(str_contains($e->getMessage(),'unidades_produccion_chasis_unique'))throw ValidationException::withMessages(['chasis_inicial'=>'Un chasis del rango acaba de ser utilizado. No se generaron unidades; revisá el rango e intentá nuevamente.']);throw $e;}
 }
}
