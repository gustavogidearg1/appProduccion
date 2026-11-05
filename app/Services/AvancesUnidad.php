<?php
namespace App\Services;
use App\Models\UnidadProduccion;
use App\Models\EtapaProduccion;
use App\Models\AvanceProduccion;
class AvancesUnidad {
    // Representación derivada: no crea ni modifica registros individuales.
    public static function efectivos(UnidadProduccion $unidad, $registrados=null) {
        $unidad->loadMissing(['avances.etapa','planProducto.plan.avances.etapa']);
        $avances=($registrados ?? $unidad->avances)->map(fn($a)=>$a);
        foreach(EtapaProduccion::where('alcance','unidad')->where('activo',true)->orderBy('orden')->get() as $etapa){
            if(!$avances->contains('etapa_id',$etapa->id)){
                $pendiente=new AvanceProduccion(['etapa_id'=>$etapa->id,'unidad_produccion_id'=>$unidad->id,'estado'=>'pendiente']);
                $pendiente->setRelation('etapa',$etapa);$avances->push($pendiente);
            }
        }
        $propio=$avances->first(fn($a)=>$a->etapa?->codigo==='ARMADO');
        if($propio && ($propio->estado!=='pendiente' || $propio->fecha_inicio || $propio->fecha_fin))return $avances;
        $montaje=$unidad->planProducto?->plan?->avances->first(fn($a)=>$a->etapa?->codigo==='LINEA_MONTAJE' && in_array($a->estado,['en_proceso','completada']));
        if(!$montaje)return $avances;
        $etapa=$propio?->etapa ?? EtapaProduccion::where('codigo','ARMADO')->where('activo',true)->first();
        if(!$etapa)return $avances;
        $armado=new AvanceProduccion(['etapa_id'=>$etapa->id,'unidad_produccion_id'=>$unidad->id,'estado'=>$montaje->estado,'fecha_inicio'=>$montaje->fecha_inicio,'fecha_fin'=>$montaje->fecha_fin]);
        $armado->setRelation('etapa',$etapa);$armado->setAttribute('heredado_plan',true);
        return $avances->reject(fn($a)=>$a->etapa_id===$etapa->id)->push($armado)->values();
    }
}
