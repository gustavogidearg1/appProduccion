<?php
namespace App\Services;
use App\Models\EtapaProduccion;
use App\Models\UnidadProduccion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class EstimadorEntrega {
    public const ZONA = 'America/Argentina/Buenos_Aires';
    private $plazos;
    private $cierres;
    private $demoras;
    private $planificado;
    public function __construct() {
        $this->plazos = DB::table('producto_plazos_entrega')->where('activo',true)->get()->keyBy(fn($r)=>$r->producto_id.':'.$r->etapa_id);
        $this->cierres = DB::table('calendario_no_laboral')->where('activo',true)->get();
        $this->demoras = DB::table('demoras_produccion')->whereNull('resuelta_at')->get();
        $this->planificado = EtapaProduccion::where('codigo','PLANIFICADO')->first();
    }
    public function habil(CarbonImmutable $fecha): bool {
        return !$fecha->isWeekend() && !$this->cierres->contains(fn($r)=>$r->desde <= $fecha->toDateString() && $r->hasta >= $fecha->toDateString());
    }
    public function sumarHabiles($fecha, int $dias): CarbonImmutable {
        $f = CarbonImmutable::parse($fecha,self::ZONA)->startOfDay();
        for ($i=0; $dias>0 || !$this->habil($f); $i++) {
            if ($i>20000) throw new \RuntimeException('El calendario supera el rango de cálculo.');
            $f=$f->addDay();
            if ($this->habil($f) && $dias>0) $dias--;
        }
        return $f;
    }
    public function calcular(UnidadProduccion $unidad): array {
        $hoy=CarbonImmutable::now(self::ZONA)->startOfDay();
        $unidad->loadMissing(['avances.etapa','planProducto.plan.avances.etapa']);
        $plan=$unidad->planProducto?->plan;
        $individual=$unidad->avances->whereIn('estado',['en_proceso','completada'])->sortByDesc(fn($a)=>$a->etapa?->orden ?? 0);
        $generales=$plan?->avances->whereIn('estado',['en_proceso','completada'])->sortByDesc(fn($a)=>$a->etapa?->orden ?? 0) ?? collect();
        $avance=$individual->first() ?? $generales->first();
        $etapa=$avance?->etapa ?? $this->planificado;
        $base=['explicacion'=>[], 'vencida'=>false, 'demoras_activas'=>[], 'origen'=>$individual->isNotEmpty() ? 'unidad' : 'plan', 'fecha'=>null,'etapa'=>$etapa?->nombre ?? 'Sin etapa','dias'=>null,'demora'=>false,'impacto'=>0,'original'=>$plan?->fecha_fin_planificada?->toDateString()];
        $entregada=$unidad->estado==='entregada' || $unidad->avances->contains(fn($a)=>$a->etapa?->codigo==='ENTREGADO' && $a->estado==='completada');
        if ($entregada) return $base+['texto'=>'Entregada','inmediata'=>false,'excluida'=>true];
        if ($plan?->estado==='cancelado') return $base+['texto'=>'Plan anulado','inmediata'=>false,'excluida'=>true];
        if ($unidad->estado==='cancelada') return $base+['texto'=>'Cancelada','inmediata'=>false,'excluida'=>true];
        $demoras=$this->demoras->filter(fn($d)=>(int)$d->plan_produccion_id===(int)$plan?->id && (!$d->unidad_produccion_id || (int)$d->unidad_produccion_id===(int)$unidad->id));
        $base['demoras_activas']=$demoras->map(fn($d)=>['motivo'=>$d->motivo,'dias'=>$d->dias_habiles,'alcance'=>$d->unidad_produccion_id ? 'Chasis' : 'Plan'])->values()->all();
        $impacto=(int)$demoras->sum('dias_habiles');
        $base['impacto']=$impacto;
        $base['demora']=$demoras->isNotEmpty() || ($plan?->fecha_fin_planificada && $plan->fecha_fin_planificada->toDateString() < $hoy->toDateString());
        $ok=$unidad->avances->contains(fn($a)=>$a->etapa?->codigo==='OK_CALIDAD' && $a->estado==='completada');
        if ($ok && $demoras->isEmpty()) return array_replace($base,['explicacion'=>['OK Calidad completada: entrega inmediata.'],'fecha'=>$hoy->toDateString(),'dias'=>0,'texto'=>'Inmediata','inmediata'=>true,'excluida'=>false]);
        $regla=$this->plazos->get($unidad->planProducto?->producto_id.':'.$etapa?->id);
        if (!$regla && !$ok) return array_replace($base,['explicacion'=>['No hay un plazo configurado para este producto y la etapa '.$base['etapa'].'.'],'texto'=>'Plazo sin definir','inmediata'=>false,'excluida'=>false]);
        $dias=$ok ? 0 : (int)$regla->dias_habiles;
        $ancla=$avance?->fecha_fin ?? $avance?->fecha_inicio ?? $plan?->fecha_planificada;
        if (!$ancla && !$ok && !$regla->desde_hoy) return $base+['texto'=>'Fecha de referencia sin definir','inmediata'=>false,'excluida'=>false];
        $original=$ancla ? $this->sumarHabiles($ancla,$dias) : null;
        if ($plan?->fecha_fin_planificada && (!$original || $plan->fecha_fin_planificada->toDateString()>$original->toDateString())) $original=$this->sumarHabiles($plan->fecha_fin_planificada,0);
        $desdeHoy=$ok || (bool)$regla->desde_hoy;
        $referencia=$ancla ? CarbonImmutable::parse($ancla,self::ZONA)->format('d/m/Y') : null;
        $tipoReferencia=$avance?->fecha_fin ? 'Fin real de etapa' : ($avance?->fecha_inicio ? 'Inicio real de etapa' : 'Fecha planificada del plan');
        $explicacion=[
            'Etapa utilizada: '.$base['etapa'].' ('.($base['origen']==='unidad' ? 'avance del chasis' : 'avance general del plan').').',
            'Plazo configurado: '.$dias.' días hábiles restantes.',
            $referencia ? $tipoReferencia.': '.$referencia.'.' : 'Sin fecha histórica de referencia.',
        ];
        if ($plan?->fecha_fin_planificada) $explicacion[]='Finalización planificada original del plan: '.$plan->fecha_fin_planificada->format('d/m/Y').'.';
        if ($original) $explicacion[]='Previsión de referencia: '.$original->format('d/m/Y').' (respeta la finalización planificada del plan si es posterior).';
        $prevista=$original ? $this->sumarHabiles($original,$impacto) : null;
        $vencida=($prevista && $prevista->lessThan($hoy)) || ($plan?->fecha_fin_planificada && $plan->fecha_fin_planificada->toDateString()<$hoy->toDateString());
        if ($desdeHoy) {
            $fecha=$this->sumarHabiles($this->sumarHabiles($hoy,$dias),$impacto);
            $explicacion[]='Modo desde hoy: '.$hoy->format('d/m/Y').' + '.$dias.' días hábiles.';
        } elseif ($prevista && !$prevista->lessThan($hoy)) {
            $fecha=$prevista;
            $explicacion[]='Previsión vigente: se conserva; no se reinicia el plazo cada día.';
        } else {
            $fecha=$this->sumarHabiles($this->sumarHabiles($hoy,$dias),$impacto);
            $explicacion[]='Previsión vencida: disponibilidad recalculada desde '.$hoy->format('d/m/Y').' sin cambiar las fechas planificadas.';
        }
        if ($impacto) $explicacion[]='Impacto adicional de demoras activas: '.$impacto.' días hábiles.';
        $explicacion[]='Se excluyen sábados y domingos. El día de referencia no cuenta como primer día del plazo.';
        $desdeCalendario=($desdeHoy || !$prevista || $prevista->lessThan($hoy)) ? $hoy->toDateString() : CarbonImmutable::parse($ancla,self::ZONA)->toDateString();
        foreach ($this->cierres as $cierre) {
            if ($cierre->desde <= $fecha->toDateString() && $cierre->hasta >= $desdeCalendario) {
                $explicacion[]='Calendario no laboral: '.CarbonImmutable::parse($cierre->desde)->format('d/m/Y').' al '.CarbonImmutable::parse($cierre->hasta)->format('d/m/Y').' · '.$cierre->motivo.'.';
            }
        }
        $explicacion[]='Resultado: '.$fecha->format('d/m/Y').'.';
        return array_replace($base,['fecha'=>$fecha->toDateString(),'dias'=>$dias,'original'=>$original?->toDateString(),
            'demora'=>$demoras->isNotEmpty() || $vencida,'vencida'=>(bool)$vencida,'explicacion'=>$explicacion,
            'texto'=>$fecha->format('d/m/Y'),'inmediata'=>false,'excluida'=>false]);

    }
}
