<?php

namespace Tests\Feature;

use App\Models\{HallazgoCalidad, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, Storage};
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HallazgosCalidadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite' => ['driver'=>'sqlite','database'=>':memory:','foreign_key_constraints'=>true], 'database.connections.club_comofra' => ['driver'=>'sqlite','database'=>':memory:','foreign_key_constraints'=>true]]);
        DB::purge('sqlite'); DB::purge('club_comofra');
        Schema::create('planes_produccion', fn (Blueprint $t) => $t->id());
        Schema::create('unidades_produccion', fn (Blueprint $t) => $t->id());
        Schema::connection('club_comofra')->create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('email'); $t->boolean('activo'); $t->unsignedBigInteger('company_id'); });
        (require base_path('database/migrations/2026_11_11_120000_create_calidad_hallazgos.php'))->up();
        foreach ([1,2,3] as $id) DB::connection('club_comofra')->table('users')->insert(['id'=>$id,'name'=>'Usuario '.$id,'email'=>"u$id@example.test",'activo'=>true,'company_id'=>$id===3 ? 2 : 1]);
        Storage::fake('local');
    }
    private function usuario(int $id): User { return User::findOrFail($id); }
    private function registrar(): HallazgoCalidad
    {
        $this->actingAs($this->usuario(1))->post(route('hallazgos.store'), ['tipo'=>'NC','origen'=>'Producción interna','titulo'=>'Soldadura incompleta','descripcion'=>'Revisar unión','responsable_id'=>2])->assertRedirect();
        return HallazgoCalidad::firstOrFail();
    }
    private function seguimiento(HallazgoCalidad $h, string $accion, array $extra=[])
    {
        return $this->post(route('hallazgos.actualizar',$h), ['accion_form'=>$accion,'version'=>$h->historial()->max('id'),'detalle'=>'Resultado documentado']+$extra);
    }
    public function test_circuito_y_permisos_conservan_historial(): void
    {
        $h=$this->registrar();
        $datos=['causa'=>'Faltó repasar','accion'=>'Rehacer unión','fecha_compromiso'=>today()->addDay()->format('Y-m-d')];
        $this->seguimiento($h,'responder',$datos)->assertForbidden();
        $this->actingAs($this->usuario(2));
        $this->seguimiento($h,'responder',$datos)->assertRedirect();
        $this->assertSame('en_accion',$h->fresh()->estado);
        $this->seguimiento($h,'terminar')->assertRedirect();
        $this->seguimiento($h,'validar')->assertForbidden();
        $this->actingAs($this->usuario(1));
        $this->seguimiento($h,'devolver')->assertRedirect();
        $this->actingAs($this->usuario(2));
        $this->seguimiento($h,'terminar')->assertRedirect();
        $this->actingAs($this->usuario(1));
        $this->seguimiento($h,'validar')->assertRedirect();
        $this->assertSame('cerrado',$h->fresh()->estado);
        $this->assertSame(6,$h->historial()->count());
        $this->get(route('hallazgos.show',$h))->assertOk()->assertSee('Soldadura incompleta');
        $this->get(route('hallazgos.index'))->assertOk()->assertDontSee('Soldadura incompleta');
        $this->get(route('hallazgos.index',['estado'=>'todos']))->assertOk()->assertSee('Soldadura incompleta');
    }
    public function test_aislamiento_empresa_y_version_desactualizada(): void
    {
        $h=$this->registrar();
        $this->actingAs($this->usuario(3))->get(route('hallazgos.show',$h))->assertNotFound();
        $this->actingAs($this->usuario(2))->post(route('hallazgos.actualizar',$h),['accion_form'=>'terminar','version'=>0,'detalle'=>'Prueba'])->assertStatus(409);
        $this->assertSame('abierto',$h->fresh()->estado);
    }
    public function test_foto_privada_y_formulario(): void
    {
        $this->actingAs($this->usuario(1))->get(route('hallazgos.create'))->assertOk()->assertSee('Responsable de la acción');
        $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6SAAAAABJRU5ErkJggg==');
        $archivo=UploadedFile::fake()->createWithContent('evidencia.png',$png);
        $this->post(route('hallazgos.store'),['tipo'=>'OBS','origen'=>'Producción interna','titulo'=>'Revisión visual','descripcion'=>'Foto de referencia','responsable_id'=>2,'foto'=>$archivo])->assertRedirect();
        $h=HallazgoCalidad::firstOrFail(); $m=$h->historial()->first();
        Storage::disk('local')->assertExists($m->foto);
        $this->get(route('hallazgos.foto',[$h,$m]))->assertOk();
        $this->actingAs($this->usuario(3))->get(route('hallazgos.foto',[$h,$m]))->assertNotFound();
    }
}
