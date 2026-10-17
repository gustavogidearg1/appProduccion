<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sectors';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'calidad',
        'active',
    ];

    protected $casts = [
        'calidad' => 'boolean',
        'active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'sector_user',
            'sector_id',
            'user_id'
        )->withPivot([
            'principal',
            'active',
        ])->withTimestamps();
    }
}
