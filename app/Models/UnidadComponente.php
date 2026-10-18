<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnidadComponente extends Model
{
    use HasFactory;

    protected $table = 'unidad_componentes';

    protected $fillable = [
        'unidad_produccion_id',
        'producto_id',
        'cantidad',
        'estado',
        'uso',
        'trazabilidad',
        'fecha_instalacion',
        'user_id',
        'observaciones',
    ];

    protected $casts = [
        'unidad_produccion_id' => 'integer',
        'producto_id' => 'integer',
        'cantidad' => 'decimal:2',
        'fecha_instalacion' => 'date',
        'user_id' => 'integer',
    ];

    public function unidadProduccion(): BelongsTo
    {
        return $this->belongsTo(
            UnidadProduccion::class,
            'unidad_produccion_id'
        );
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
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
