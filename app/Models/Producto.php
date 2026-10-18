<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Producto extends Model
{
    use HasFactory;

    protected $connection = 'cotizador';

    protected $table = 'productos';

    protected $casts = [
        'codigo' => 'integer',
        'activo' => 'boolean',
        'familia_id' => 'integer',
        'um_id' => 'integer',
        'tipo_id' => 'integer',
        'user_id' => 'integer',
        'ue_produccion' => 'decimal:2',
    ];

    public function familia(): BelongsTo
    {
        return $this->belongsTo(
            Familia::class,
            'familia_id',
            'id'
        );
    }

    public function componentesEstandar(): HasMany
{
    return $this->hasMany(
        ProductoComponente::class,
        'producto_id'
    );
}

public function tipo(): BelongsTo
{
    return $this->belongsTo(
        Tipo::class,
        'tipo_id'
    );
}

}
