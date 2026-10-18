<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtapaProduccion extends Model
{
    use HasFactory;

    protected $table = 'etapas_produccion';

    protected $fillable = [
        'codigo',
        'nombre',
        'orden',
        'alcance',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function avances(): HasMany
{
    return $this->hasMany(
        AvanceProduccion::class,
        'etapa_id'
    );
}
}
