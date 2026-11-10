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
        $productos=Producto::with(['familia','tipo'])->where('activo',true)->orderBy('nombre')->get();
        $productoId=$r->integer('producto_id');
        $etapas=EtapaProduccion::where('activo',true)->where('codigo','!=','ENTREGADO')->orderBy('orden')->get();
        $etapas = \App\Services\SectoresProduccion::ordenar($etapas->where('alcance', 'plan'))
            ->concat($etapas->where('alcance', '!=', 'plan'));
        $reglas=DB::table('producto_plazos_entrega')->where('producto_id',$productoId)->get()->keyBy('etapa_id');
        $cierres=DB::table('calendario_no_laboral')->orderByDesc('desde')->paginate(20)->withQueryString();
        return view('planificacion.configuracion',compact('productos','productoId','etapas','reglas','cierres'));
    }
    public function guardar(Request $r) {
        $plazos = $r->input('plazos');
        if (is_array($plazos)) {
            foreach ($plazos as &$regla) {
                if (is_array($regla) && is_string($regla['ue'] ?? null)) {
                    $regla['ue'] = str_replace(',', '.', trim($regla['ue']));
                }
            }
            unset($regla);
            $r->merge(['plazos' => $plazos]);
        }

        $datos=$r->validate(['producto_id'=>['required','integer',Rule::exists(Producto::class,'id')],
            'plazos'=>['required','array'],'plazos.*.dias'=>['nullable','integer','min:0','max:3650'],
            'plazos.*.ue'=>['nullable','numeric','decimal:0,4','min:0','max:999999.9999'],
            'plazos.*.desde_hoy'=>['nullable','boolean']]);
        $etapas=EtapaProduccion::where('activo',true)->where('codigo','!=','ENTREGADO')->pluck('codigo','id');
        DB::transaction(function()use($datos,$etapas){
            foreach($datos['plazos'] as $id=>$regla){
                abort_unless($etapas->has((int)$id),422,'Etapa inválida.');
                $key = ['producto_id' => $datos['producto_id'], 'etapa_id' => $id];
                $dias = $etapas->get((int) $id) === 'OK_CALIDAD' ? null : ($regla['dias'] ?? null);
                $ue = $regla['ue'] ?? null;
                $values = ['activo' => $dias !== null, 'updated_at' => now()];
                if (array_key_exists('ue', $regla)) {
                    $values['ue'] = $ue;
                }
                if ($dias !== null) {
                    $values['dias_habiles'] = $dias;
                    $values['desde_hoy'] = (bool) ($regla['desde_hoy'] ?? false);
                }

                if ($dias === null && $ue === null) {
                    DB::table('producto_plazos_entrega')->where($key)->update($values);
                    continue;
                }

                DB::table('producto_plazos_entrega')->upsert(
                    [$key + $values + ['dias_habiles' => 0, 'created_at' => now()]],
                    ['producto_id', 'etapa_id'],
                    array_keys($values)
                );
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
