<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Familia extends Model
{
    use HasFactory;

    protected $connection = 'cotizador';

    protected $table = 'familias';

    protected $fillable = [
        'nombre',
        'imagen_principal',
        'imagen_secundaria',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(
            Producto::class,
            'familia_id',
            'id'
        );
    }
}
