<?php
namespace App\Http\Controllers;
use App\Models\UnidadProduccion;
use App\Models\EtapaProduccion;
use App\Models\AvanceProduccion;
use App\Models\VentaAsignacion;
use App\Models\Venta;
use App\Services\EstimadorEntrega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EntregaUnidadController extends Controller {
    public function index(UnidadProduccion $unidad) {
        $unidad->load(['planProducto.plan','planProducto.producto','avances.etapa']);
        $entrega=DB::table('entregas_unidad')->where('unidad_produccion_id',$unidad->id)->first();
        $fotos=$entrega ? DB::table('entrega_fotos')->where('entrega_id',$entrega->id)->get() : collect();
        $asignacion=$unidad->asignaciones()->whereIn('estado',['reservada','asignada'])->whereHas('detalle.venta',fn($q)=>$q->where('estado','!=','anulada'))->with('detalle.venta.cliente')->latest('id')->first();
        $pendientes=EtapaProduccion::where('activo',true)->where('alcance','unidad')->where('codigo','!=','ENTREGADO')
            ->whereNotIn('id',$unidad->avances->where('estado','completada')->pluck('etapa_id'))->orderBy('orden')->get();
        return view('planificacion.entrega',compact('unidad','entrega','fotos','asignacion','pendientes'));
    }
    private function validarFotos(Request $r): void {
        $r->validate(['fotos'=>['nullable','array','max:10'],'fotos.*'=>['required','image','mimes:jpg,jpeg,png,webp','max:5120']]);
    }
    private function guardarFotos(Request $r,int $entrega,array &$paths): void {
        foreach($r->file('fotos',[]) as $foto){
            $path=$foto->store('entregas/'.$entrega,'local');
            if(!$path) throw new \RuntimeException('No se pudo guardar la foto.');
            $paths[]=$path;
            DB::table('entrega_fotos')->insert(['entrega_id'=>$entrega,'path'=>$path,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function guardar(Request $r,UnidadProduccion $unidad) {
        $this->validarFotos($r);
        $d=$r->validate(['fecha'=>['required','date','before_or_equal:'.now(EstimadorEntrega::ZONA)->toDateString()],
            'recibido_por'=>['required','string','max:255'],'identificacion_ok'=>['accepted'],'documentacion_ok'=>['accepted'],
            'accesorios_ok'=>['accepted'],'observaciones'=>['nullable','string','max:5000']]);
        $paths=[];
        try {
            DB::transaction(function()use($unidad,$r,$d,&$paths){
                $unidad=UnidadProduccion::whereKey($unidad->id)->lockForUpdate()->firstOrFail();
                if($unidad->estado==='entregada' || $unidad->estado==='cancelada' || DB::table('entregas_unidad')->where('unidad_produccion_id',$unidad->id)->exists())
                    throw ValidationException::withMessages(['entrega'=>'La unidad ya fue entregada o está cancelada.']);
                $etapa=EtapaProduccion::where('codigo','ENTREGADO')->where('activo',true)->where('alcance','unidad')->firstOrFail();
                $pendientes=EtapaProduccion::where('activo',true)->where('alcance','unidad')->where('orden','<',$etapa->orden)
                    ->whereNotIn('id',$unidad->avances()->where('estado','completada')->pluck('etapa_id'))->exists();
                if($pendientes) throw ValidationException::withMessages(['entrega'=>'Completá las etapas anteriores, incluido OK Calidad, antes de confirmar la entrega.']);
                $asignacion=$unidad->asignaciones()->whereIn('estado',['reservada','asignada'])->latest('id')->first();
                if(!$asignacion) throw ValidationException::withMessages(['entrega'=>'La unidad no tiene una venta asignada.']);
                $venta=Venta::whereKey($asignacion->detalle->venta_id)->lockForUpdate()->firstOrFail();
                $asignacion=VentaAsignacion::whereKey($asignacion->id)->lockForUpdate()->firstOrFail();
                if(!in_array($venta->estado,['confirmada','entregada']) || !in_array($asignacion->estado,['reservada','asignada']))
                    throw ValidationException::withMessages(['entrega'=>'La entrega requiere una venta confirmada y una reserva vigente.']);
                $id=DB::table('entregas_unidad')->insertGetId([
                    'unidad_produccion_id'=>$unidad->id,'venta_asignacion_id'=>$asignacion->id,'fecha'=>$d['fecha'],
                    'recibido_por'=>$d['recibido_por'],'responsable'=>auth()->user()?->name ?? 'Usuario autenticado',
                    'identificacion_ok'=>true,'documentacion_ok'=>true,'accesorios_ok'=>true,'observaciones'=>$d['observaciones']??null,
                    'created_at'=>now(),'updated_at'=>now()]);
                $this->guardarFotos($r,$id,$paths);
                $usuario=auth()->id();
                if($usuario && !DB::table('users')->where('id',$usuario)->exists()) $usuario=null;
                AvanceProduccion::updateOrCreate(['unidad_produccion_id'=>$unidad->id,'etapa_id'=>$etapa->id],
                    ['estado'=>'completada','fecha_inicio'=>$d['fecha'],'fecha_fin'=>$d['fecha'],'iniciado_por'=>$usuario,'finalizado_por'=>$usuario,'observaciones'=>$d['observaciones']??null]);
                $unidad->update(['estado'=>'entregada']);
                $asignacion->update(['estado'=>'asignada']);
                $venta->historial()->create(['user_id'=>$usuario,'accion'=>'unidad_entregada','datos'=>['unidad_produccion_id'=>$unidad->id,'entrega_id'=>$id,'fecha'=>$d['fecha'],'recibido_por'=>$d['recibido_por']]]);
            });
        } catch(\Throwable $e){foreach($paths as $p) Storage::disk('local')->delete($p);throw $e;}
        return back()->with('success','Entrega registrada con su control de salida.');
    }
    public function agregarFotos(Request $r,UnidadProduccion $unidad) {
        $this->validarFotos($r); $r->validate(['fotos'=>['required','array','min:1','max:10']]);
        $paths=[];
        try { DB::transaction(function()use($r,$unidad,&$paths){
            $entrega=DB::table('entregas_unidad')->where('unidad_produccion_id',$unidad->id)->lockForUpdate()->first();abort_unless($entrega,404);
            abort_if(DB::table('entrega_fotos')->where('entrega_id',$entrega->id)->count()+count($r->file('fotos',[]))>20,422,'Máximo 20 fotos por entrega.');
            $this->guardarFotos($r,$entrega->id,$paths);
        }); } catch(\Throwable $e){foreach($paths as $p)Storage::disk('local')->delete($p);throw $e;}
        return back()->with('success','Fotos agregadas a la entrega.');
    }
    public function foto(UnidadProduccion $unidad,int $foto) {
        $row=DB::table('entrega_fotos')->join('entregas_unidad','entregas_unidad.id','=','entrega_fotos.entrega_id')
            ->where('entrega_fotos.id',$foto)->where('entregas_unidad.unidad_produccion_id',$unidad->id)->select('entrega_fotos.path')->first();
        abort_unless($row && Storage::disk('local')->exists($row->path),404);
        return response()->file(Storage::disk('local')->path($row->path),['X-Content-Type-Options'=>'nosniff']);
    }
}
