<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  foreach(['AGROPARTES'=>'Agropartes','LINEA_MONTAJE'=>'Línea de montaje','PREPINTURA'=>'Prepintura'] as $codigo=>$nombre){
   if(!DB::table('etapas_produccion')->where('codigo',$codigo)->exists())DB::table('etapas_produccion')->insert(['codigo'=>$codigo,'nombre'=>$nombre,'alcance'=>'plan','orden'=>5,'activo'=>true,'created_at'=>now(),'updated_at'=>now()]);
  }
 }
 public function down(): void {
  // Conservar sectores y registros históricos al revertir código.
 }
};
