<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanProducto extends Model
{
    use HasFactory;

    protected $table = 'plan_productos';

    protected $fillable = [
        'plan_produccion_id',
        'producto_id',
        'cantidad',
        'observaciones',
    ];

    protected $casts = [
        'plan_produccion_id' => 'integer',
        'producto_id' => 'integer',
        'cantidad' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            PlanProduccion::class,
            'plan_produccion_id'
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
