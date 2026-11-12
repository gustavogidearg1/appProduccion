<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallazgoCalidad extends Model
{
    protected $table = 'calidad_hallazgos';
    protected $guarded = ['id'];
    protected $casts = ['fecha_compromiso' => 'date'];

    public const TIPOS = ['NC' => 'No conformidad', 'OBS' => 'Observación', 'OM' => 'Oportunidad de mejora'];
    public const ESTADOS = ['abierto' => 'Pendiente de respuesta', 'en_accion' => 'En acción', 'por_verificar' => 'Pendiente de verificación', 'cerrado' => 'Cerrado'];
    public const ORIGENES = ['Producción interna', 'Posventa', 'Auditoría interna', 'Auditoría externa', 'Modificación de producto'];

    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function autor() { return $this->belongsTo(User::class, 'autor_id'); }
    public function plan() { return $this->belongsTo(PlanProduccion::class, 'plan_produccion_id'); }
    public function unidad() { return $this->belongsTo(UnidadProduccion::class, 'unidad_produccion_id'); }
    public function historial() { return $this->hasMany(HallazgoMovimiento::class, 'hallazgo_id')->orderBy('id'); }
}
