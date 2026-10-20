<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoControlCalidad extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'producto_controles_calidad';

    protected $fillable = [
        'producto_id',
        'control_calidad_id',
        'orden',
        'obligatorio',
        'activo',
    ];

    protected $casts = [
        'producto_id' => 'integer',
        'control_calidad_id' => 'integer',
        'orden' => 'integer',
        'obligatorio' => 'boolean',
        'activo' => 'boolean',
    ];

    public function control(): BelongsTo
    {
        return $this->belongsTo(
            ControlCalidad::class,
            'control_calidad_id'
        );
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }
}
