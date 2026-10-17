<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permiso extends SpatiePermission
{
    protected $connection = 'club_comofra';

    protected $table = 'permissions';
}
