<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id',
        'fecha',
        'fecha_entrega_pactada',
        'estado',
        'total',
        'observaciones',
    ];

    protected $casts = [
        'cliente_id' => 'integer',
        'fecha' => 'date',
        'fecha_entrega_pactada' => 'date',
        'total' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(
            VentaDetalle::class,
            'venta_id'
        );
    }

    public function historial(): HasMany
    {
        return $this->hasMany(
            VentaHistorial::class,
            'venta_id'
        )->latest();
    }
}
