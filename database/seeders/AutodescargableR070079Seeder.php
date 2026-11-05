<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Storage};
use App\Models\{Familia,CalidadSeccion,EtapaProduccion};
use App\Services\ConfiguracionCalidad as C;
class AutodescargableR070079Seeder extends Seeder {
 public function run(): void {
  $ruta=resource_path('calidad/autodescargable-r070079');$datos=json_decode(file_get_contents($ruta.'/datos.json'),true,512,JSON_THROW_ON_ERROR);
  $f=Familia::where('nombre','Auto descargable')->firstOrFail();$e=C::estado($f);
  if(collect($e['c']['items'])->contains(fn($i)=>($i['origen_archivo_sha256']??null)===$datos['sha256'])){$this->command?->info('La planilla ya está cargada: no se duplicaron controles.');return;}
  $etapas=EtapaProduccion::where('alcance','unidad')->where('activo',true)->pluck('id','codigo');foreach($datos['items'] as $i)if(!isset($etapas[$i['etapa_codigo']]))throw new \RuntimeException('Etapa sin configurar: '.$i['etapa_codigo']);
  // Images are kept privately; only authenticated revision routes serve them.
  foreach($datos['imagenes'] as $img){$path='calidad/referencias/importacion-'.$datos['sha256'].'/'.$img['archivo'];if(!Storage::disk('local')->exists($path))Storage::disk('local')->put($path,file_get_contents($ruta.'/'.$img['archivo']));}
  C::guardar($f,$e['version'],function(&$c,$estado)use($f,$datos,$etapas){
   $orden=(int)(CalidadSeccion::where('familia_id',$f->id)->max('orden')??0);
   $resolver=function($nombre)use($f,&$orden){$s=CalidadSeccion::where('familia_id',$f->id)->where('nombre',$nombre)->first();if(!$s){$orden+=10;$s=CalidadSeccion::create(['familia_id'=>$f->id,'nombre'=>$nombre,'orden'=>$orden,'activo'=>true]);}return $s;};
   foreach($c['items'] as &$i){if(empty($i['seccion_id'])){$s=$resolver(trim($i['seccion']??'')?:'Controles existentes');$i['seccion_id']=$s->id;$i['seccion']=$s->nombre;$i['seccion_orden']=$s->orden;$i['seccion_activa']=$s->activo;}}unset($i);
   foreach($datos['items'] as $i){$s=$resolver($i['seccion']);$i['etapa_id']=$etapas[$i['etapa_codigo']];unset($i['etapa_codigo']);$i['seccion_id']=$s->id;$i['seccion_orden']=$s->orden;$i['seccion_activa']=$s->activo;$i['origen_archivo_sha256']=$datos['sha256'];$i['origen_hoja']=$datos['hoja'];$i['origen_codigo']=$datos['codigo'];$c['items'][]=$i;}
   foreach($datos['imagenes'] as $img){$c['imagenes'][]=['seccion'=>$img['seccion'],'pie'=>$img['pie'],'path'=>'calidad/referencias/importacion-'.$datos['sha256'].'/'.$img['archivo'],'origen_archivo_sha256'=>$datos['sha256']];}
   $c['fuentes'][]=['codigo'=>$datos['codigo'],'sha256'=>$datos['sha256'],'hoja'=>$datos['hoja'],'familia_confirmada'=>'Auto descargable','version_fuente'=>$datos['version_fuente'],'nota'=>'Asignación a Auto descargable confirmada por el usuario aunque el documento fuente identifica Batea. No se importaron hojas Batea anterior, 26000 ni Hoja3.'];
  });
  $doc=DB::table('calidad_documentos')->where('familia_id',$f->id)->first();
  if(!$e['doc'])DB::table('calidad_documentos')->where('id',$doc->id)->update(['codigo'=>$datos['codigo'],'nombre'=>'Planilla de autocontrol Auto descargable','updated_at'=>now()]);
  $r=DB::table('calidad_revisiones')->where('documento_id',$doc->id)->where('estado','borrador')->first();
  if(!$r->motivo)DB::table('calidad_revisiones')->where('id',$r->id)->update(['motivo'=>'Carga inicial desde R.07.00.79, hoja BATEA NUEVO, asignada a Auto descargable por indicación del usuario. Fuente con versión 006 en primera página y 005 en las posteriores; revisar antes de aprobar.','updated_at'=>now()]);
  $this->command?->info('Se agregaron '.count($datos['items']).' controles y 6 imágenes a Auto descargable. Borrador '.$r->id.'; sin aprobación.');
 }
}
