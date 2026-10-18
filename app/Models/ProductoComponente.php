<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoComponente extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'producto_componentes';

    protected $fillable = [
        'producto_id',
        'componente_producto_id',
        'cantidad',
        'obligatorio',
        'activo',
        'uso',
        'observaciones',
    ];

    protected $casts = [
        'producto_id' => 'integer',
        'componente_producto_id' => 'integer',
        'cantidad' => 'decimal:2',
        'obligatorio' => 'boolean',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }

    public function componenteProducto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'componente_producto_id'
        );
    }
}
