<?php
namespace App\Services;
class SectoresProduccion {
    public const CODIGOS=['CORTE','PLEGADO','MEZANIZADO','AGROPARTES','LINEA_MONTAJE','PREPINTURA'];
    private const PREVIAS=['PLANIFICADO'=>[], 'LANZADO'=>['PLANIFICADO'], 'CORTE'=>[], 'PLEGADO'=>['CORTE'], 'MEZANIZADO'=>[], 'AGROPARTES'=>['PLEGADO'], 'LINEA_MONTAJE'=>['MEZANIZADO','AGROPARTES'], 'PREPINTURA'=>['LINEA_MONTAJE']];
    public static function previas(string $codigo): array {return self::PREVIAS[$codigo] ?? [];}
    public static function posteriores(string $codigo): array {
        $resultado=[];
        foreach(self::PREVIAS as $c=>$previas)if(in_array($codigo,$previas,true))$resultado=array_merge($resultado,[$c],self::posteriores($c));
        return array_values(array_unique($resultado));
    }
    public static function ordenar($etapas) {return $etapas->sortBy(fn($e)=>array_search($e->codigo,array_merge(['PLANIFICADO','LANZADO'],self::CODIGOS),true) === false ? 99 : array_search($e->codigo,array_merge(['PLANIFICADO','LANZADO'],self::CODIGOS),true))->values();}
}
