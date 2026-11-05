<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Storage};
use App\Models\{Familia,EtapaProduccion};
class FertilizanteAutocontrolSeeder extends Seeder {
 public function run(): void {
  $f=Familia::whereRaw('LOWER(nombre) = ?', ['fertilizante'])->firstOrFail();
  DB::transaction(function()use($f){
   if(DB::table('calidad_documentos')->where('familia_id',$f->id)->exists())return;
   $data=json_decode(file_get_contents(resource_path('calidad/fertilizante/borrador.json')),true,512,JSON_THROW_ON_ERROR);
   foreach($data['items'] as &$item){$e=EtapaProduccion::where('codigo',$item['etapa_codigo'])->where('alcance','unidad')->firstOrFail();$item['etapa_id']=$e->id;unset($item['etapa_codigo']);}unset($item);
   foreach($data['imagenes'] as &$img){$path='calidad/referencias/fertilizante-'.substr($data['sha256'],0,12).'/'.$img['archivo'];Storage::disk('local')->put($path,file_get_contents(resource_path('calidad/fertilizante/'.$img['archivo'])));$img['path']=$path;unset($img['archivo']);}unset($img);
   $id=DB::table('calidad_documentos')->insertGetId(['familia_id'=>$f->id,'codigo'=>'R.07.00.76','nombre'=>'Planilla de autocontrol Fertilizante','created_at'=>now(),'updated_at'=>now()]);
   DB::table('calidad_revisiones')->insert(['documento_id'=>$id,'numero'=>1,'estado'=>'borrador','contenido'=>json_encode($data),'motivo'=>'Transcripción inicial desde la hoja FERTILIZANTE del Excel. Revisar aplicabilidad, criterios y etapas antes de aprobar. La revisión documental del original no se presume.','preparado_por'=>'Importación desde Excel · pendiente de revisión por Calidad','created_at'=>now(),'updated_at'=>now()]);
  });
 }
}
