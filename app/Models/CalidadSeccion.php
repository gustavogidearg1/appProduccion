<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CalidadSeccion extends Model {
 protected $table='calidad_secciones';
 protected $fillable=['familia_id','nombre','orden','activo'];
 protected $casts=['familia_id'=>'integer','orden'=>'integer','activo'=>'boolean'];
 public function familia(){return $this->belongsTo(Familia::class);}
 public function controles(){return $this->hasMany(ControlCalidad::class,'calidad_seccion_id');}
}
