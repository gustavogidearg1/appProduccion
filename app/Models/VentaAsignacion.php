<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaAsignacion extends Model
{
    use HasFactory;

    protected $table = 'venta_asignacions';

    protected $fillable = [
        'venta_detalle_id',
        'unidad_produccion_id',
        'estado',
        'fecha_reserva',
        'user_id',
        'observaciones',
    ];

    protected $casts = [
        'venta_detalle_id' => 'integer',
        'unidad_produccion_id' => 'integer',
        'user_id' => 'integer',
        'fecha_reserva' => 'datetime',
    ];

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(
            VentaDetalle::class,
            'venta_detalle_id'
        );
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(
            UnidadProduccion::class,
            'unidad_produccion_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
