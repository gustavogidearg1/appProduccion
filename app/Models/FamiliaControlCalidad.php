<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamiliaControlCalidad extends Model
{
    use HasFactory;

    protected $table = 'familia_controles_calidad';

    protected $fillable = [
        'familia_id',
        'control_calidad_id',
        'etapa_produccion_id',
        'orden',
        'obligatorio',
        'activo',
        'respuesta_esperada',
        'unidad_medida',
        'valor_minimo',
        'valor_maximo',
    ];

    protected $casts = [
        'familia_id' => 'integer',
        'control_calidad_id' => 'integer',
        'etapa_produccion_id' => 'integer',
        'orden' => 'integer',
        'obligatorio' => 'boolean',
        'activo' => 'boolean',
        'valor_minimo' => 'decimal:3',
        'valor_maximo' => 'decimal:3',
    ];

    public function control(): BelongsTo
    {
        return $this->belongsTo(
            ControlCalidad::class,
            'control_calidad_id'
        );
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(
            EtapaProduccion::class,
            'etapa_produccion_id'
        );
    }
}
