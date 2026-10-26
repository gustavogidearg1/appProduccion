<?php
namespace App\Http\Controllers;
use App\Models\Producto;
use App\Models\EtapaProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlazoEntregaController extends Controller {
    public function index(Request $r) {
        $r->validate(['producto_id'=>['nullable','integer',Rule::exists(Producto::class,'id')]]);
        $productos=Producto::where('activo',true)->orderBy('nombre')->get();
        $productoId=$r->integer('producto_id');
        $etapas=EtapaProduccion::where('activo',true)->whereNotIn('codigo',['ENTREGADO','OK_CALIDAD'])->orderBy('orden')->get();
        $reglas=DB::table('producto_plazos_entrega')->where('producto_id',$productoId)->get()->keyBy('etapa_id');
        $cierres=DB::table('calendario_no_laboral')->orderByDesc('desde')->paginate(20)->withQueryString();
        return view('planificacion.configuracion',compact('productos','productoId','etapas','reglas','cierres'));
    }
    public function guardar(Request $r) {
        $datos=$r->validate(['producto_id'=>['required','integer',Rule::exists(Producto::class,'id')],
            'plazos'=>['required','array'],'plazos.*.dias'=>['nullable','integer','min:0','max:3650'],
            'plazos.*.desde_hoy'=>['nullable','boolean']]);
        $etapas=EtapaProduccion::where('activo',true)->whereNotIn('codigo',['ENTREGADO','OK_CALIDAD'])->pluck('id');
        DB::transaction(function()use($datos,$etapas){
            foreach($datos['plazos'] as $id=>$regla){
                abort_unless($etapas->contains((int)$id),422,'Etapa inválida.');
                if (($regla['dias'] ?? null)===null || $regla['dias']==='') {
                    DB::table('producto_plazos_entrega')->where('producto_id',$datos['producto_id'])->where('etapa_id',$id)->update(['activo'=>false,'updated_at'=>now()]);
                } else {
                    $key=['producto_id'=>$datos['producto_id'],'etapa_id'=>$id];
                    $existing=DB::table('producto_plazos_entrega')->where($key)->first();
                    DB::table('producto_plazos_entrega')->updateOrInsert($key,['dias_habiles'=>$regla['dias'],'desde_hoy'=>(bool)($regla['desde_hoy']??false),'activo'=>true,'created_at'=>$existing?->created_at ?? now(),'updated_at'=>now()]);
                }
            }
        });
        return redirect()->route('plazos.index',['producto_id'=>$datos['producto_id']])->with('success','Plazos guardados. Las fechas originales no se modificaron.');
    }
    public function calendario(Request $r) {
        $d=$r->validate(['desde'=>['required','date'],'hasta'=>['required','date','after_or_equal:desde'],
            'tipo'=>['required',Rule::in(['feriado','no_laboral','vacaciones'])],'motivo'=>['required','string','max:255']]);
        DB::table('calendario_no_laboral')->insert($d+['activo'=>true,'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Período no laboral registrado.');
    }
    public function alternar(int $cierre) {
        DB::transaction(function()use($cierre){
            $row=DB::table('calendario_no_laboral')->where('id',$cierre)->lockForUpdate()->first(); abort_unless($row,404);
            DB::table('calendario_no_laboral')->where('id',$cierre)->update(['activo'=>!$row->activo,'updated_at'=>now()]);
        });
        return back()->with('success','Calendario actualizado.');
    }
}
