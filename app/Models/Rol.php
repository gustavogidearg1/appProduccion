<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Rol extends SpatieRole
{
    protected $connection = 'club_comofra';

    protected $table = 'roles';
}
