<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipo extends Model
{
    protected $connection = 'cotizador';

    protected $table = 'tipos';
}
