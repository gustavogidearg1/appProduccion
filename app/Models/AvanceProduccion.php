<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvanceProduccion extends Model
{
    use HasFactory;

    protected $table = 'avances_produccion';

    protected $fillable = [
        'etapa_id',
        'plan_produccion_id',
        'unidad_produccion_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'iniciado_por',
        'finalizado_por',
        'observaciones',
    ];

    protected $casts = [
        'etapa_id' => 'integer',
        'plan_produccion_id' => 'integer',
        'unidad_produccion_id' => 'integer',
        'iniciado_por' => 'integer',
        'finalizado_por' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(
            EtapaProduccion::class,
            'etapa_id'
        );
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            PlanProduccion::class,
            'plan_produccion_id'
        );
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(
            UnidadProduccion::class,
            'unidad_produccion_id'
        );
    }
}
