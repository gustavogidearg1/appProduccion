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
        $base=['fecha'=>null,'etapa'=>$etapa?->nombre ?? 'Sin etapa','dias'=>null,'demora'=>false,'impacto'=>0,'original'=>$plan?->fecha_fin_planificada?->toDateString()];
        $entregada=$unidad->estado==='entregada' || $unidad->avances->contains(fn($a)=>$a->etapa?->codigo==='ENTREGADO' && $a->estado==='completada');
        if ($entregada) return $base+['texto'=>'Entregada','inmediata'=>false,'excluida'=>true];
        if ($unidad->estado==='cancelada') return $base+['texto'=>'Cancelada','inmediata'=>false,'excluida'=>true];
        $demoras=$this->demoras->filter(fn($d)=>(int)$d->plan_produccion_id===(int)$plan?->id && (!$d->unidad_produccion_id || (int)$d->unidad_produccion_id===(int)$unidad->id));
        $impacto=(int)$demoras->sum('dias_habiles');
        $base['impacto']=$impacto;
        $base['demora']=$demoras->isNotEmpty() || ($plan?->fecha_fin_planificada && $plan->fecha_fin_planificada->toDateString() < $hoy->toDateString());
        $ok=$unidad->avances->contains(fn($a)=>$a->etapa?->codigo==='OK_CALIDAD' && $a->estado==='completada');
        if ($ok && $demoras->isEmpty()) return array_replace($base,['fecha'=>$hoy->toDateString(),'dias'=>0,'texto'=>'Inmediata','inmediata'=>true,'excluida'=>false]);
        $regla=$this->plazos->get($unidad->planProducto?->producto_id.':'.$etapa?->id);
        if (!$regla && !$ok) return $base+['texto'=>'Plazo sin definir','inmediata'=>false,'excluida'=>false];
        $dias=$ok ? 0 : (int)$regla->dias_habiles;
        $ancla=$avance?->fecha_fin ?? $avance?->fecha_inicio ?? $plan?->fecha_planificada;
        if (!$ancla && !$ok && !$regla->desde_hoy) return $base+['texto'=>'Fecha de referencia sin definir','inmediata'=>false,'excluida'=>false];
        $original=$ancla ? $this->sumarHabiles($ancla,$dias) : null;
        if ($plan?->fecha_fin_planificada && (!$original || $plan->fecha_fin_planificada->toDateString()>$original->toDateString())) $original=$this->sumarHabiles($plan->fecha_fin_planificada,0);
        $minima=$this->sumarHabiles($hoy,$dias);
        $fecha=(!$ok && !$regla->desde_hoy && $original && $original->greaterThan($minima)) ? $original : $minima;
        $fecha=$this->sumarHabiles($fecha,$impacto);
        return array_replace($base,['fecha'=>$fecha->toDateString(),'dias'=>$dias,'original'=>$original?->toDateString(),
            'demora'=>$base['demora'] || ($original && $original->lessThan($hoy)),
            'texto'=>$fecha->format('d/m/Y'),'inmediata'=>false,'excluida'=>false]);
    }
}
