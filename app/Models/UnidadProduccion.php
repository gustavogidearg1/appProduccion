<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadProduccion extends Model
{
    use HasFactory;

    protected $table = 'unidades_produccion';

    protected $fillable = [
        'plan_producto_id',
        'numero_unidad',
        'chasis',
        'serie',
        'cliente_id',
        'estado',
        'color',
        'observaciones',
    ];

    protected $casts = [
        'plan_producto_id' => 'integer',
        'numero_unidad' => 'integer',
        'cliente_id' => 'integer',
    ];

    public function planProducto(): BelongsTo
    {
        return $this->belongsTo(
            PlanProducto::class,
            'plan_producto_id'
        );
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    public function componentes(): HasMany
{
    return $this->hasMany(
        UnidadComponente::class,
        'unidad_produccion_id'
    );
}

public function avances(): HasMany
{
    return $this->hasMany(
        AvanceProduccion::class,
        'unidad_produccion_id'
    );
}

}
