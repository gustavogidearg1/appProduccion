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

    public function administraAvancesGlobales(): bool
    {
        return $this->activo && $this->hasAnyRole(['admin_sitio', 'admin_empresa']);
    }

    public function puedeOperarEtapaGlobal(EtapaProduccion $etapa): bool
    {
        if ($this->administraAvancesGlobales()) {
            return true;
        }

        if (!$this->activo || !$this->hasRole('empleado')
            || !in_array($etapa->codigo, \App\Services\SectoresProduccion::CODIGOS, true)) {
            return false;
        }

        $normalizar = fn ($valor) => \Illuminate\Support\Str::slug((string) $valor, '_');
        $codigo = $normalizar($etapa->codigo);
        $equivalencias = [
            'plegado' => ['pleg'],
            'mezanizado' => ['mecanizado'],
            'linea_montaje' => ['linea_de_montaje'],
        ];

        return $this->sectors->contains(function (Sector $sector) use ($normalizar, $codigo, $equivalencias) {
            if (!$sector->active || !$sector->pivot->active || $sector->company_id != $this->company_id) {
                return false;
            }

            $identificadores = [$codigo, ...($equivalencias[$codigo] ?? [])];

            return in_array($normalizar($sector->code), $identificadores, true)
                || in_array($normalizar($sector->name), $identificadores, true);
        });
    }

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
