<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class EstadoMantenimiento
{
    public static function calcular(?string $ultima, ?int $frecuencia, ?string $inicial, ?string $hoy = null): array
    {
        $hoy = CarbonImmutable::parse($hoy ?? today()->toDateString())->startOfDay();
        $fecha = $ultima && $frecuencia ? CarbonImmutable::parse($ultima)->addDays($frecuencia) : ($ultima ? null : ($inicial ? CarbonImmutable::parse($inicial) : null));
        if (!$fecha) return ['fecha'=>null, 'estado'=>$ultima ? 'sin_programacion' : 'sin_registro'];
        $estado = $fecha->lt($hoy) ? 'vencida' : ($fecha->lte($hoy->addDays(7)) ? 'proxima' : 'al_dia');
        return ['fecha'=>$fecha->toDateString(), 'estado'=>$estado];
    }
    public const ETIQUETAS = ['vencida'=>'Vencida','proxima'=>'Próximos 7 días','al_dia'=>'Al día','sin_registro'=>'Sin registro ni fecha inicial','sin_programacion'=>'Sin próxima fecha','sin_plan'=>'Sin plan preventivo'];
}
