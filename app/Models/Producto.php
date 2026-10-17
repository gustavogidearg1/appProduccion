<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    use HasFactory;

    protected $connection = 'cotizador';

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'um_id',
        'detalle',
        'img',
        'img_1',
        'img_2',
        'img_3',
        'links',
        'familia_id',
        'activo',
        'tipo_id',
        'user_id',
    ];

    protected $casts = [
        'codigo' => 'integer',
        'activo' => 'boolean',
        'familia_id' => 'integer',
        'um_id' => 'integer',
        'tipo_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function familia(): BelongsTo
    {
        return $this->belongsTo(
            Familia::class,
            'familia_id',
            'id'
        );
    }
}
