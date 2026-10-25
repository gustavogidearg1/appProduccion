<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaHistorial extends Model
{
    use HasFactory;

    protected $table = 'venta_historials';

    protected $fillable = [
        'venta_id',
        'user_id',
        'accion',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'datos',
    ];

    protected $casts = [
        'venta_id' => 'integer',
        'user_id' => 'integer',
        'datos' => 'array',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(
            Venta::class,
            'venta_id'
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
