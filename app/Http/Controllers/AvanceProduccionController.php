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

        $correcciones = \Illuminate\Support\Facades\DB::table('avances_correcciones')
            ->join('etapas_produccion','etapas_produccion.id','=','avances_correcciones.etapa_id')
            ->where('unidad_produccion_id',$unidad->id)->select('avances_correcciones.*','etapas_produccion.nombre as etapa_nombre')->orderByDesc('avances_correcciones.id')->paginate(20);

        return view(
            'produccion.planes.avances.unidad',
            compact(
                'plan',
                'planProducto',
                'unidad',
                'etapas',
                'avances',
                'correcciones'
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
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificar avances.');

        $this->validarRelaciones($plan, $planProducto, $unidad);
        if ($etapa->codigo === 'ENTREGADO') return redirect()->route('entregas.index',$unidad);


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
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificar avances.');

        $this->validarRelaciones($plan, $planProducto, $unidad);
        if ($etapa->codigo === 'ENTREGADO') return redirect()->route('entregas.index',$unidad);


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
                ->where('resultado', 'pendiente')
                ->count();

            if ($controlesPendientes > 0) {
                return back()->with(
                    'error',
                    'No se puede finalizar OK Calidad. Hay controles obligatorios pendientes.'
                );
            }
        }

        $avance->update([
            'estado' => 'completada',
            'fecha_fin' => now(),
            'finalizado_por' => auth()->id(),
            'observaciones' => $request->input('observaciones'),
        ]);

        if ($etapa->codigo === 'OK_CALIDAD') $unidad->update(['estado'=>'terminada']);


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

        $versionReales = hash('sha256', json_encode($avances->sortBy('id')->map(fn($a)=>$a->getRawOriginal())->values()->all()));
        $correccionesPlan = \Illuminate\Support\Facades\DB::table('avances_plan_correcciones')->where('plan_produccion_id',$plan->id)->join('etapas_produccion','etapas_produccion.id','=','avances_plan_correcciones.etapa_id')->select('avances_plan_correcciones.*','etapas_produccion.nombre as etapa_nombre')->orderByDesc('avances_plan_correcciones.id')->paginate(20);

        return view(
            'produccion.planes.avances.plan',
            compact('plan', 'etapas', 'avances', 'versionReales', 'correccionesPlan')
        );
    }

    public function corregirFechasPlan(Request $request, PlanProduccion $plan, EtapaProduccion $etapa) {
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificar avances.');

        abort_unless($etapa->alcance==='plan' && $etapa->activo,404);
        $d=$request->validate(['fecha_inicio'=>['nullable','date'],'fecha_fin'=>['nullable','date'],
            'motivo'=>['required','string','max:5000'],'version'=>['required','string','size:64'],
            'reiniciar_posteriores'=>['nullable','boolean']]);
        $inicio=!empty($d['fecha_inicio']) ? \Carbon\Carbon::parse($d['fecha_inicio']) : null;
        $fin=!empty($d['fecha_fin']) ? \Carbon\Carbon::parse($d['fecha_fin']) : null;
        $error=function($mensaje){throw \Illuminate\Validation\ValidationException::withMessages(['fechas_reales'=>$mensaje]);};
        if($fin && !$inicio)$error('Para indicar un fin real también debe existir el inicio real.');
        if($fin && $fin->lt($inicio))$error('El fin real no puede ser anterior al inicio real.');
        if(($inicio && $inicio->isFuture()) || ($fin && $fin->isFuture()))$error('Las fechas reales no pueden estar en el futuro.');
        $estado=$fin ? 'completada' : ($inicio ? 'en_proceso' : 'pendiente');
        \Illuminate\Support\Facades\DB::transaction(function()use($plan,$etapa,$d,$inicio,$fin,$estado,$error){
            $plan=PlanProduccion::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $avances=$plan->avances()->with('etapa')->lockForUpdate()->get();
            $version=hash('sha256',json_encode($avances->sortBy('id')->map(fn($a)=>$a->getRawOriginal())->values()->all()));
            if(!hash_equals($version,$d['version']))$error('El plan cambió mientras editabas. Actualizá la página antes de corregir.');
            $posteriores=$avances->filter(fn($a)=>$a->etapa?->orden>$etapa->orden && ($a->estado!=='pendiente' || $a->fecha_inicio || $a->fecha_fin));
            if($estado!=='completada' && $posteriores->isNotEmpty() && empty($d['reiniciar_posteriores']))$error('Confirmá el reinicio de las etapas generales posteriores para quitar estas fechas.');
            if($inicio){
                $previas=EtapaProduccion::where('alcance','plan')->where('activo',true)->where('orden','<',$etapa->orden)->get();
                foreach($previas as $previa){$a=$avances->firstWhere('etapa_id',$previa->id);
                    if(!$a || $a->estado!=='completada')$error('Primero debe completarse '.$previa->nombre.'.');
                    if($a->fecha_fin && $inicio->lt($a->fecha_fin))$error('El inicio debe ser posterior o igual al fin de '.$previa->nombre.'.');
                }
            }
            if($fin)foreach($posteriores as $a)if($a->fecha_inicio && $fin->gt($a->fecha_inicio))$error('El fin supera el inicio de una etapa posterior. Corregí primero esa etapa.');
            $avance=$avances->firstWhere('etapa_id',$etapa->id);
            $antes=['etapa'=>$avance?->getAttributes(),'posteriores'=>[]];
            if($estado!=='completada')foreach($posteriores as $a){$antes['posteriores'][]=$a->getAttributes();$a->update(['estado'=>'pendiente','fecha_inicio'=>null,'fecha_fin'=>null,'iniciado_por'=>null,'finalizado_por'=>null]);}
            $usuario=auth()->id();$local=$usuario && \Illuminate\Support\Facades\DB::table('users')->where('id',$usuario)->exists() ? $usuario : null;
            $avance ??= new AvanceProduccion(['plan_produccion_id'=>$plan->id,'etapa_id'=>$etapa->id]);
            $avance->fill(['estado'=>$estado,'fecha_inicio'=>$inicio,'fecha_fin'=>$fin,
                'iniciado_por'=>$inicio ? ($avance->iniciado_por ?? $local) : null,
                'finalizado_por'=>$fin ? ($avance->finalizado_por ?? $local) : null]);$avance->save();
            $nuevo=['etapa'=>$avance->getAttributes(),'posteriores'=>$plan->avances()->whereIn('id',$posteriores->pluck('id'))->get()->map(fn($a)=>$a->getAttributes())->all()];
            \Illuminate\Support\Facades\DB::table('avances_plan_correcciones')->insert(['plan_produccion_id'=>$plan->id,'etapa_id'=>$etapa->id,'motivo'=>$d['motivo'],
                'responsable'=>auth()->user()?->name ?? 'Usuario autenticado','usuario_id'=>$usuario,'anterior'=>json_encode($antes),'nuevo'=>json_encode($nuevo),'created_at'=>now(),'updated_at'=>now()]);
        });
        return back()->with('success','Fechas reales corregidas. Se conservaron las fechas planificadas y el historial.');
    }

    public function updatePlan(
    Request $request,
    PlanProduccion $plan,
    EtapaProduccion $etapa
) {
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificar avances.');

    if (
        $etapa->alcance !== 'plan' ||
        !$etapa->activo
    ) {
        abort(404);
    }

    $datos = $request->validate([
        'fecha_inicio_planificada' => [
            'nullable',
            'date',
        ],

        'fecha_fin_planificada' => [
            'nullable',
            'date',
            'after_or_equal:fecha_inicio_planificada',
        ],
    ]);

    $avance = AvanceProduccion::firstOrCreate(
        [
            'plan_produccion_id' => $plan->id,
            'etapa_id' => $etapa->id,
        ],
        [
            'estado' => 'pendiente',
        ]
    );

    $avance->update([
        'fecha_inicio_planificada' =>
            $datos['fecha_inicio_planificada'] ?? null,

        'fecha_fin_planificada' =>
            $datos['fecha_fin_planificada'] ?? null,
    ]);

    return back()->with(
        'success',
        'Fechas planificadas actualizadas correctamente.'
    );
}

    public function iniciarPlan(
        PlanProduccion $plan,
        EtapaProduccion $etapa
    ) {
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificar avances.');

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
        abort_if($plan->estado==='cancelado',422,'El plan está anulado. Reactivalo antes de modificar avances.');

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
