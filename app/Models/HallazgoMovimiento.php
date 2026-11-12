<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallazgoMovimiento extends Model
{
    protected $table = 'calidad_hallazgo_movimientos';
    protected $guarded = ['id'];
    public function usuario() { return $this->belongsTo(User::class, 'usuario_id'); }
}
