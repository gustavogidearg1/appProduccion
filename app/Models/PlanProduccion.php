<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;



class PlanProduccion extends Model
{
    use HasFactory;

    protected $table = 'planes_produccion';

    protected $fillable = [
        'numero',
        'estado',
        'fecha_planificada',
        'prioridad',
        'observaciones',
    ];

    protected $casts = [
        'numero' => 'integer',
        'fecha_planificada' => 'date',
        'prioridad' => 'integer',
    ];

    public function planProductos(): HasMany
{
    return $this->hasMany(
        PlanProducto::class,
        'plan_produccion_id'
    );
}

public function avances(): HasMany
{
    return $this->hasMany(
        AvanceProduccion::class,
        'plan_produccion_id'
    );
}


}
