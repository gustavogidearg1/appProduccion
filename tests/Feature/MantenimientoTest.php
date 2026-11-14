<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Services\EstadoMantenimiento;
use Illuminate\Support\Facades\{DB,Schema};
class MantenimientoAdminPrueba extends User {
 public function administraAvancesGlobales(): bool { return true; }
}
class MantenimientoTest extends TestCase {
 protected function setUp(): void {
  parent::setUp();
  config(['database.default'=>'sqlite','database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true], 'database.connections.club_comofra'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'session.driver'=>'array']);
  DB::purge('sqlite'); DB::purge('club_comofra');
  (require database_path('migrations/2026_11_13_230000_create_mantenimiento.php'))->up();
  (require database_path('migrations/2026_11_14_000000_create_mantenimiento_fotos.php'))->up();
  Schema::connection('club_comofra')->create('users',function($t){$t->id();$t->string('name');$t->string('email')->nullable();$t->boolean('activo');$t->unsignedBigInteger('company_id');});
  $u=new MantenimientoAdminPrueba; $u->forceFill(['id'=>1,'name'=>'Prueba','activo'=>true,'company_id'=>1]); $this->actingAs($u);
 }
 public function test_graficos_respetan_empresa_y_fechas_de_cierre(): void {
  $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-13'));
  $base=['autor_id'=>1,'autor_nombre'=>'Prueba','company_id'=>1,'titulo'=>'Prueba gráfico','descripcion'=>'Detalle','prioridad'=>'alta','created_at'=>'2026-10-01 00:00:00','updated_at'=>now()];
  DB::table('mantenimiento_ordenes')->insert([
   $base+['estado'=>'pendiente','fecha_compromiso'=>'2026-11-01','fecha_fin'=>null],
   $base+['estado'=>'finalizada','fecha_compromiso'=>null,'fecha_fin'=>'2026-11-05'],
   array_replace($base,['company_id'=>2,'estado'=>'pendiente','fecha_compromiso'=>null,'fecha_fin'=>null])]);
  $this->get('/mantenimiento/ordenes')->assertOk()->assertViewHas('conteos',fn($c)=>$c->sum()==2)
   ->assertViewHas('prioridades',fn($p)=>$p['alta']==1)->assertViewHas('vencidas',1)
   ->assertViewHas('evolucion',fn($e)=>$e[4]['creadas']==2 && $e[5]['finalizadas']==1 && $e[5]['creadas']==0)
   ->assertSee('Evolución mensual')->assertDontSee('@include(');
  $this->travelBack();
 }
 public function test_fechas_no_inventa_y_detecta_vencimiento(): void {
  $this->assertNull(EstadoMantenimiento::calcular(null,30,null,'2026-11-13')['fecha']);
  $this->assertSame('2026-11-12',EstadoMantenimiento::calcular('2026-10-13',30,null,'2026-11-13')['fecha']);
  $this->assertSame('vencida',EstadoMantenimiento::calcular('2026-10-13',30,null,'2026-11-13')['estado']);
 }
 public function test_circuito_preventivo_y_aislamiento(): void {
  $this->post('/mantenimiento/maquinas',['numero'=>'M1','nombre'=>'Prensa','grado'=>1,'estado'=>'activa'])->assertRedirect();
  $id=DB::table('mantenimiento_maquinas')->value('id');
  $this->post("/mantenimiento/maquinas/$id/tareas",['nombre'=>'Lubricar','frecuencia_dias'=>30,'activa'=>1])->assertRedirect();
  $t=DB::table('mantenimiento_tareas')->value('id');
  $this->post("/mantenimiento/maquinas/$id/tareas/$t/registros",['version'=>1,'fecha'=>today()->toDateString(),'intervencion'=>'Lubricación realizada'])->assertRedirect();
  $this->assertSame(1,DB::table('mantenimiento_registros')->count());
  $this->post("/mantenimiento/maquinas/$id/tareas/$t/registros",['version'=>1,'fecha'=>today()->toDateString(),'intervencion'=>'Duplicada'])->assertSessionHasErrors('version');
  $this->get('/mantenimiento')->assertOk()->assertSee('Prensa');
  $this->get("/mantenimiento/maquinas/$id")->assertOk()->assertSee('Lubricación realizada')->assertSee('name="nombre"',false)->assertDontSee("@include(");
  $this->get('/mantenimiento/calendario')->assertOk();
  DB::table('mantenimiento_maquinas')->where('id',$id)->update(['company_id'=>2]);
  $this->get("/mantenimiento/maquinas/$id")->assertNotFound();
 }
 public function test_orden_con_historial_y_cierre(): void {
  $this->post('/mantenimiento/ordenes',['titulo'=>'Falla motor','descripcion'=>'No enciende','prioridad'=>'alta'])->assertRedirect();
  $id=DB::table('mantenimiento_ordenes')->value('id');
  $data=['detalle'=>'Trabajo registrado','fecha'=>today()->toDateString()];
  $this->post("/mantenimiento/ordenes/$id",$data+['accion'=>'finalizar','version'=>1])->assertSessionHasErrors('fecha');
  $this->post("/mantenimiento/ordenes/$id",$data+['accion'=>'iniciar','version'=>1])->assertRedirect();
  $this->post("/mantenimiento/ordenes/$id",$data+['accion'=>'finalizar','version'=>2])->assertRedirect();
  $this->assertSame('finalizada',DB::table('mantenimiento_ordenes')->value('estado'));
  $this->assertSame(3,DB::table('mantenimiento_movimientos')->count());
  $this->get('/mantenimiento/ordenes?estado=todas')->assertOk()->assertSee('Falla motor');
  $this->get("/mantenimiento/ordenes/$id")->assertOk();
 }
 public function test_fotos_correo_edicion_y_anulacion(): void {
  \Illuminate\Support\Facades\Mail::fake();
  \Illuminate\Support\Facades\Storage::fake('local');
  DB::connection('club_comofra')->table('users')->insert([
   ['id'=>1,'name'=>'Autor','email'=>'autor@example.com','activo'=>1,'company_id'=>1],
   ['id'=>2,'name'=>'Responsable','email'=>'responsable@example.com','activo'=>1,'company_id'=>1]]);
  $this->post('/mantenimiento/ordenes',['titulo'=>'Motor','descripcion'=>'Falla inicial','prioridad'=>'alta','responsable_id'=>2,'fotos'=>[\Illuminate\Http\UploadedFile::fake()->createWithContent('antes.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aS1sAAAAASUVORK5CYII='))]])->assertRedirect()->assertSessionHasNoErrors();
  $id=DB::table('mantenimiento_ordenes')->value('id');
  \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\MantenimientoAsignado::class,fn($m)=>$m->hasTo('responsable@example.com') && $m->hasCc('autor@example.com'));
  $foto=DB::table('mantenimiento_fotos')->first();
  $this->get("/mantenimiento/ordenes/$id/fotos/$foto->id")->assertOk();
  $base=['fecha'=>today()->toDateString(),'detalle'=>'Cambio registrado'];
  $this->post("/mantenimiento/ordenes/$id",$base+['accion'=>'editar','version'=>1,'titulo'=>'Motor corregido','descripcion'=>'Detalle corregido','prioridad'=>'media'])->assertSessionHasNoErrors();
  $this->assertSame('Motor corregido',DB::table('mantenimiento_ordenes')->value('titulo'));
  $this->post("/mantenimiento/ordenes/$id",$base+['accion'=>'iniciar','version'=>2])->assertSessionHasNoErrors();
  $this->post("/mantenimiento/ordenes/$id",$base+['accion'=>'finalizar','version'=>3,'fotos'=>[\Illuminate\Http\UploadedFile::fake()->createWithContent('despues.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aS1sAAAAASUVORK5CYII='))]])->assertSessionHasNoErrors();
  $this->assertSame(1,DB::table('mantenimiento_fotos')->where('tipo','final')->count());
  $this->post("/mantenimiento/ordenes/$id",$base+['accion'=>'reabrir','version'=>4])->assertSessionHasNoErrors();
  $this->post("/mantenimiento/ordenes/$id",$base+['accion'=>'anular','version'=>5])->assertSessionHasNoErrors();
  $this->assertSame('anulada',DB::table('mantenimiento_ordenes')->value('estado'));
  DB::table('mantenimiento_ordenes')->where('id',$id)->update(['company_id'=>2]);
  $this->get("/mantenimiento/ordenes/$id/fotos/$foto->id")->assertNotFound();
 }
}
