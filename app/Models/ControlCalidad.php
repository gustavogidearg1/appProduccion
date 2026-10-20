<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlCalidad extends Model
{
    use HasFactory;

    protected $table = 'controles_calidad';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_respuesta',
        'orden',
        'obligatorio',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'obligatorio' => 'boolean',
        'activo' => 'boolean',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(
            ProductoControlCalidad::class,
            'control_calidad_id'
        );
    }

    public function controlesUnidades(): HasMany
    {
        return $this->hasMany(
            UnidadControlCalidad::class,
            'control_calidad_id'
        );
    }
}
