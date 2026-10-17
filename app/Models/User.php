<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $connection = 'club_comofra';

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'cuil',
        'direccion',
        'telefono',
        'company_id',
        'pais_id',
        'provincia_id',
        'localidad_id',
        'imagen',
        'fecha_nacimiento',
        'activo',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'fecha_nacimiento' => 'date',
        'activo' => 'boolean',
        'password' => 'hashed',
    ];

    protected string $guard_name = 'web';

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(
            Sector::class,
            'sector_user',
            'user_id',
            'sector_id'
        )->withPivot([
            'principal',
            'active',
        ])->withTimestamps();
    }
}
