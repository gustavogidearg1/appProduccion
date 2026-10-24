<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\EtapaProduccion;


class UnidadControlCalidad extends Model
{
    use HasFactory;

    protected $table = 'unidad_controles_calidad';

    protected $fillable = [
        'unidad_produccion_id',
        'control_calidad_id',
        'resultado',
        'valor',
        'observaciones',
        'evidencia_path',
        'user_id',
        'fecha_control',
        'etapa_produccion_id',
    ];

    protected $casts = [
        'unidad_produccion_id' => 'integer',
        'control_calidad_id' => 'integer',
        'user_id' => 'integer',
        'fecha_control' => 'datetime',
    ];

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(
            UnidadProduccion::class,
            'unidad_produccion_id'
        );
    }

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
