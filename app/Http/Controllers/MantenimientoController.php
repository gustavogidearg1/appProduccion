<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EstadoMantenimiento as Estado;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Mail, Storage};
use App\Mail\MantenimientoAsignado;
use Illuminate\Support\Str;
use Illuminate\Validation\{Rule, ValidationException};

class MantenimientoController extends Controller
{
    private function empresa(Request $q): int
    {
        abort_unless($q->user()->activo && $q->user()->company_id, 403);
        return (int) $q->user()->company_id;
    }
    private function puedeEditar(Request $q): bool
    {
        $this->empresa($q);
        if ($q->user()->administraAvancesGlobales()) return true;
        return $q->user()->sectors->contains(fn ($s) => $s->active && $s->pivot->active && $s->company_id == $q->user()->company_id && (in_array(Str::slug($s->code ?? ''), ['mant','mantenimiento']) || Str::slug($s->name) === 'mantenimiento'));
    }
    private function escritura(Request $q): void { abort_unless($this->puedeEditar($q), 403, 'Se requiere el sector Mantenimiento o un administrador.'); }
    private function maquinas(Request $q) { return DB::table('mantenimiento_maquinas')->where('company_id', $this->empresa($q)); }
    private function equipo(Request $q, int $id, bool $lock = false)
    {
        $query = $this->maquinas($q)->where('id', $id);
        $m = ($lock ? $query->lockForUpdate() : $query)->first(); abort_unless($m, 404); return $m;
    }
    private function version(Request $q, $fila): void
    {
        if ((string) $fila->version !== (string) $q->input('version')) throw ValidationException::withMessages(['version'=>'Otro usuario actualizó este registro. Recargá antes de guardar.']);
    }
    private function responsables(Request $q) { return User::where('company_id', $this->empresa($q))->where('activo', true)->orderBy('name')->get(['id','name','email']); }
    private function responsable(Request $q): ?User
    {
        if (!$q->filled('responsable_id')) return null;
        $u = $this->responsables($q)->firstWhere('id', (int) $q->input('responsable_id'));
        if (!$u) throw ValidationException::withMessages(['responsable_id'=>'Seleccioná un usuario activo de la empresa.']);
        return $u;
    }
    private function tareas(Request $q)
    {
        $ultimas = DB::table('mantenimiento_registros')->whereNull('anulado_en')->select('tarea_id', DB::raw('MAX(fecha) as ultima'))->groupBy('tarea_id');
        return DB::table('mantenimiento_tareas as t')->join('mantenimiento_maquinas as m','m.id','=','t.maquina_id')
            ->leftJoinSub($ultimas,'r',fn ($j) => $j->on('r.tarea_id','=','t.id'))
            ->where('m.company_id', $this->empresa($q))->where('m.estado','activa')->where('t.activa',true)
            ->select('t.*','m.numero','m.nombre as maquina_nombre','m.marca','m.sector','m.grado','r.ultima')->get()
            ->map(function ($t) { $t->prevision = Estado::calcular($t->ultima, $t->frecuencia_dias, $t->fecha_inicial); return $t; });
    }
    public function index(Request $q)
    {
        $editar = $this->puedeEditar($q); $tareas = $this->tareas($q); $etiquetas = Estado::ETIQUETAS;
        $todas = $this->maquinas($q)->when(!$q->boolean('inactivas'),fn ($b) => $b->where('estado','activa'))->orderBy('grado')->orderBy('numero')->get();
        $estadisticas = collect(array_keys($etiquetas))->mapWithKeys(fn ($e) => [$e=>$e === 'sin_plan' ? $todas->filter(fn ($m) => !$tareas->contains('maquina_id',$m->id))->count() : $tareas->filter(fn ($t) => $t->prevision['estado']===$e)->count()]);
        $maquinas = $todas->filter(function ($m) use ($q,$tareas) {
            if ($q->filled('buscar') && !Str::contains(Str::lower($m->numero.' '.$m->nombre.' '.$m->marca.' '.$m->sector),Str::lower(trim($q->input('buscar'))))) return false;
            if ($q->filled('grado') && $m->grado != $q->input('grado')) return false;
            if ($q->filled('situacion')) { $ts=$tareas->where('maquina_id',$m->id); return $q->situacion==='sin_plan' ? $ts->isEmpty() : $ts->contains(fn ($t) => $t->prevision['estado']===$q->situacion); }
            return true;
        });
        $ordenesAbiertas = DB::table('mantenimiento_ordenes')->where('company_id',$this->empresa($q))->whereIn('estado',['pendiente','en_proceso'])->count();
        return view('mantenimiento.index', compact('editar','tareas','maquinas','estadisticas','etiquetas','ordenesAbiertas'));
    }
    public function maquinaForm(Request $q) { $this->escritura($q); $m=null; return view('mantenimiento.maquina-form',compact('m')); }
    public function maquinaGuardar(Request $q, ?int $id = null)
    {
        $this->escritura($q); $empresa=$this->empresa($q);
        $d=$q->validate(['numero'=>['required','string','max:60',Rule::unique('mantenimiento_maquinas')->where('company_id',$empresa)->ignore($id)],'nombre'=>'required|string|max:255','marca'=>'nullable|string|max:255','sector'=>'nullable|string|max:255','grado'=>'required|integer|between:1,3','estado'=>['required',Rule::in(['activa','fuera_servicio','vendida','desarmada'])],'observaciones'=>'nullable|string|max:10000']);
        $id=DB::transaction(function () use ($q,$id,$d,$empresa) {
            if ($id) { $m=$this->equipo($q,$id,true); $this->version($q,$m); DB::table('mantenimiento_maquinas')->where('id',$id)->update($d+['version'=>$m->version+1,'updated_at'=>now()]); return $id; }
            return DB::table('mantenimiento_maquinas')->insertGetId($d+['company_id'=>$empresa,'created_at'=>now(),'updated_at'=>now()]);
        });
        return redirect()->route('mantenimiento.maquinas.ver',$id)->with('success','Máquina guardada.');
    }
    public function maquina(Request $q, int $id)
    {
        $m=$this->equipo($q,$id); $editar=$this->puedeEditar($q); $etiquetas=Estado::ETIQUETAS;
        $tareas=DB::table('mantenimiento_tareas')->where('maquina_id',$id)->orderBy('id')->get();
        $registros=DB::table('mantenimiento_registros as r')->join('mantenimiento_tareas as t','t.id','=','r.tarea_id')->where('t.maquina_id',$id)->select('r.*','t.nombre as tarea_nombre')->orderByDesc('r.fecha')->orderByDesc('r.id')->get();
        foreach ($tareas as $t) { $ultima=$registros->where('tarea_id',$t->id)->whereNull('anulado_en')->max('fecha'); $t->prevision=Estado::calcular($ultima,$t->frecuencia_dias,$t->fecha_inicial); }
        return view('mantenimiento.maquina',compact('m','editar','tareas','registros','etiquetas'));
    }
    public function tareaGuardar(Request $q, int $id, ?int $tarea = null)
    {
        $this->escritura($q); $this->equipo($q,$id);
        $d=$q->validate(['nombre'=>'required|string|max:255','instrucciones'=>'nullable|string|max:10000','frecuencia_dias'=>'nullable|integer|min:1|max:3650','frecuencia_detalle'=>'nullable|string|max:255','fecha_inicial'=>'nullable|date_format:Y-m-d']);
        $d['activa']=$q->boolean('activa');
        DB::transaction(function () use ($q,$id,$tarea,$d) {
            if ($tarea) { $t=DB::table('mantenimiento_tareas')->where('id',$tarea)->where('maquina_id',$id)->lockForUpdate()->first(); abort_unless($t,404); $this->version($q,$t); DB::table('mantenimiento_tareas')->where('id',$tarea)->update($d+['version'=>$t->version+1,'updated_at'=>now()]); }
            else DB::table('mantenimiento_tareas')->insert($d+['maquina_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        });
        return back()->with('success','Tarea preventiva guardada.');
    }
    public function registrar(Request $q, int $id, int $tarea)
    {
        $this->escritura($q); $this->equipo($q,$id);
        $d=$q->validate(['fecha'=>'required|date_format:Y-m-d|before_or_equal:today','intervencion'=>'required|string|max:10000','repuestos'=>'nullable|string|max:10000','horas'=>'nullable|numeric|min:0|max:999999.99']);
        DB::transaction(function () use ($q,$id,$tarea,$d) {
            $t=DB::table('mantenimiento_tareas')->where('id',$tarea)->where('maquina_id',$id)->lockForUpdate()->first(); abort_unless($t && $t->activa,404); $this->version($q,$t);
            DB::table('mantenimiento_registros')->insert($d+['tarea_id'=>$tarea,'autor_id'=>$q->user()->id,'autor_nombre'=>$q->user()->name,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('mantenimiento_tareas')->where('id',$tarea)->update(['version'=>$t->version+1,'updated_at'=>now()]);
        });
        return back()->with('success','Intervención registrada. El historial anterior se conserva.');
    }
    public function anularRegistro(Request $q, int $id, int $registro)
    {
        $this->escritura($q); $this->equipo($q,$id); $q->validate(['motivo'=>'required|string|min:3|max:2000']);
        DB::transaction(function () use ($q,$id,$registro) {
            $r=DB::table('mantenimiento_registros')->where('id',$registro)->first(); abort_unless($r,404);
            $t=DB::table('mantenimiento_tareas')->where('id',$r->tarea_id)->where('maquina_id',$id)->lockForUpdate()->first(); abort_unless($t,404);
            DB::table('mantenimiento_registros')->where('id',$registro)->whereNull('anulado_en')->update(['anulado_en'=>now(),'anulado_por'=>$q->user()->id,'motivo_anulacion'=>$q->motivo,'updated_at'=>now()]);
            DB::table('mantenimiento_tareas')->where('id',$t->id)->update(['version'=>$t->version+1,'updated_at'=>now()]);
        });
        return back()->with('success','Registro anulado. Se conserva para consulta y se recalcula la próxima fecha.');
    }
    public function calendario(Request $q)
    {
        $q->validate(['mes'=>'nullable|date_format:Y-m']); $mes=CarbonImmutable::parse(($q->input('mes') ?: today()->format('Y-m')).'-01');
        $eventos=$this->tareas($q)->filter(fn ($t) => $t->prevision['fecha'] && substr($t->prevision['fecha'],0,7)===$mes->format('Y-m'))->groupBy(fn ($t) => $t->prevision['fecha']);
        $ordenes=DB::table('mantenimiento_ordenes')->where('company_id',$this->empresa($q))->whereIn('estado',['pendiente','en_proceso'])->whereBetween('fecha_compromiso',[$mes->toDateString(),$mes->endOfMonth()->toDateString()])->get()->groupBy('fecha_compromiso');
        return view('mantenimiento.calendario',compact('mes','eventos','ordenes'));
    }
    public function ordenes(Request $q)
    {
        $this->empresa($q); $base=DB::table('mantenimiento_ordenes')->where('company_id',$this->empresa($q));
        $conteos=(clone $base)->select('estado',DB::raw('COUNT(*) as total'))->groupBy('estado')->pluck('total','estado');
        $abiertas=(clone $base)->whereIn('estado',['pendiente','en_proceso']);
        $prioridades=(clone $abiertas)->select('prioridad',DB::raw('COUNT(*) as total'))->groupBy('prioridad')->pluck('total','prioridad');
        $vencidas=(clone $abiertas)->whereDate('fecha_compromiso','<',today())->count();
        $evolucion=[];
        for ($i=5; $i>=0; $i--) {
            $mes=today()->startOfMonth()->subMonths($i); $fin=$mes->copy()->addMonth();
            $evolucion[]=['mes'=>$mes->format('m/Y'),
                'creadas'=>(clone $base)->where('created_at','>=',$mes)->where('created_at','<',$fin)->count(),
                'finalizadas'=>(clone $base)->where('estado','finalizada')->where('fecha_fin','>=',$mes->toDateString())->where('fecha_fin','<',$fin->toDateString())->count()];
        }
        $ordenes=$base->when($q->filled('estado'),fn ($b) => $b->when($q->estado !== 'todas',fn ($b) => $b->where('estado',$q->estado)),fn ($b) => $b->whereIn('estado',['pendiente','en_proceso']))->when($q->filled('buscar'),fn ($b) => $b->where(function ($b) use ($q) { $b->where('titulo','like','%'.$q->buscar.'%')->orWhere('id',$q->buscar); }))->orderByDesc('id')->paginate(25)->withQueryString();
        return view('mantenimiento.ordenes',compact('ordenes','conteos','prioridades','vencidas','evolucion'));
    }
    public function ordenForm(Request $q)
    {
        $maquinas=$this->maquinas($q)->where('estado','activa')->orderBy('numero')->get(); $usuarios=$this->responsables($q);
        return view('mantenimiento.orden-form',compact('maquinas','usuarios'));
    }
    public function ordenCrear(Request $q)
    {
        $this->validarFotos($q);
        $empresa=$this->empresa($q);
        $d=$q->validate(['maquina_id'=>'nullable|integer','titulo'=>'required|string|max:255','descripcion'=>'required|string|max:10000','prioridad'=>['required',Rule::in(['alta','media','baja'])],'responsable_id'=>'nullable|integer','fecha_compromiso'=>'nullable|date_format:Y-m-d']);
        if (!empty($d['maquina_id'])) $this->equipo($q,(int)$d['maquina_id']); else $d['maquina_id']=null;
        $u=$this->responsable($q); $d['responsable_id']=$u?->id; $d['responsable_nombre']=$u?->name;
        $id=DB::transaction(function () use ($q,$d,$empresa) {
            $id=DB::table('mantenimiento_ordenes')->insertGetId($d+['company_id'=>$empresa,'autor_id'=>$q->user()->id,'autor_nombre'=>$q->user()->name,'created_at'=>now(),'updated_at'=>now()]);
            $this->movimiento($q,$id,'creada',$d['descripcion'],today()->toDateString()); return $id;
        });
        $this->guardarFotos($q,$id,'inicio');
        return $this->avisar($q,$id,redirect()->route('mantenimiento.ordenes.ver',$id)->with('success','Hallazgo registrado como orden de trabajo.'));
    }
    private function buscarOrden(Request $q, int $id, bool $lock=false)
    {
        $b=DB::table('mantenimiento_ordenes')->where('company_id',$this->empresa($q))->where('id',$id); $o=($lock ? $b->lockForUpdate() : $b)->first(); abort_unless($o,404); return $o;
    }
    public function orden(Request $q, int $id)
    {
        $o=$this->buscarOrden($q,$id); $m=$o->maquina_id ? $this->equipo($q,$o->maquina_id) : null; $gestionar=$this->puedeEditar($q); $editar=$gestionar || (int)$o->responsable_id===(int)$q->user()->id; $usuarios=$this->responsables($q);
        $movimientos=DB::table('mantenimiento_movimientos')->where('orden_id',$id)->orderByDesc('id')->get();
        $fotos=DB::table('mantenimiento_fotos')->where('orden_id',$id)->orderBy('id')->get();
        return view('mantenimiento.orden',compact('o','m','editar','gestionar','usuarios','movimientos','fotos'));
    }
    private function movimiento(Request $q, int $id, string $accion, string $detalle, string $fecha, array $extra=[]): void
    {
        DB::table('mantenimiento_movimientos')->insert($extra+['orden_id'=>$id,'accion'=>$accion,'detalle'=>$detalle,'fecha'=>$fecha,'autor_id'=>$q->user()->id,'autor_nombre'=>$q->user()->name,'created_at'=>now(),'updated_at'=>now()]);
    }
    public function ordenActualizar(Request $q, int $id)
    {
        $orden=$this->buscarOrden($q,$id); $gestionar=$this->puedeEditar($q);
        abort_unless($gestionar || ((int)$orden->responsable_id===(int)$q->user()->id && in_array($q->accion,['nota','iniciar','finalizar'])),403);
        $this->validarFotos($q);
        $d=$q->validate(['accion'=>['required',Rule::in(['editar','planificar','iniciar','finalizar','anular','reabrir','nota'])],'detalle'=>'required|string|min:3|max:10000','fecha'=>'required|date_format:Y-m-d|before_or_equal:today','repuestos'=>'nullable|string|max:10000','horas'=>'nullable|numeric|min:0|max:999999.99','fecha_compromiso'=>'nullable|date_format:Y-m-d','responsable_id'=>'nullable|integer']);
        DB::transaction(function () use ($q,$id,$d) {
            $o=$this->buscarOrden($q,$id,true); $this->version($q,$o); $a=$d['accion']; $c=[];
            if ($a==='editar') {
                if ($o->estado==='anulada') throw ValidationException::withMessages(['accion'=>'Reabrí la orden antes de editarla.']);
                $campos=$q->validate(['titulo'=>'required|string|max:255','descripcion'=>'required|string|max:10000','prioridad'=>['required',Rule::in(['alta','media','baja'])]]);
                $c=$campos;
                $this->movimiento($q,$id,'valores_anteriores',json_encode(['titulo'=>$o->titulo,'descripcion'=>$o->descripcion,'prioridad'=>$o->prioridad],JSON_UNESCAPED_UNICODE),$d['fecha']);
            }
            if ($a==='planificar') { if (!in_array($o->estado,['pendiente','en_proceso'])) throw ValidationException::withMessages(['accion'=>'Reabrí la orden antes de planificarla.']); $u=$this->responsable($q); $c=['responsable_id'=>$u?->id,'responsable_nombre'=>$u?->name,'fecha_compromiso'=>$d['fecha_compromiso']??null]; }
            if ($a==='iniciar') { if ($o->estado!=='pendiente') throw ValidationException::withMessages(['accion'=>'Solo se inicia una orden pendiente.']); $c=['estado'=>'en_proceso','fecha_inicio'=>$d['fecha']]; }
            if ($a==='finalizar') { if ($o->estado!=='en_proceso' || $d['fecha'] < $o->fecha_inicio) throw ValidationException::withMessages(['fecha'=>'Iniciá la orden primero. El cierre debe ser igual o posterior al inicio.']); $c=['estado'=>'finalizada','fecha_fin'=>$d['fecha']]; }
            if ($a==='anular') { if (!in_array($o->estado,['pendiente','en_proceso'])) throw ValidationException::withMessages(['accion'=>'Solo se anulan órdenes abiertas.']); $c=['estado'=>'anulada']; }
            if ($a==='reabrir') { if (!in_array($o->estado,['finalizada','anulada'])) throw ValidationException::withMessages(['accion'=>'La orden ya está abierta.']); $c=['estado'=>'pendiente','fecha_inicio'=>null,'fecha_fin'=>null]; }
            DB::table('mantenimiento_ordenes')->where('id',$id)->update($c+['version'=>$o->version+1,'updated_at'=>now()]);
            $this->movimiento($q,$id,$a,$d['detalle'],$d['fecha'],['repuestos'=>$d['repuestos']??null,'horas'=>$d['horas']??null]);
        });
        $this->guardarFotos($q,$id,$q->accion==='finalizar' ? 'final' : 'inicio');
        $respuesta=back()->with('success','Seguimiento guardado.');
        return $q->accion==='planificar' && (int)$orden->responsable_id!==(int)$q->responsable_id ? $this->avisar($q,$id,$respuesta) : $respuesta;
    }

    private function validarFotos(Request $q): void
    {
        $q->validate(['fotos'=>'nullable|array|max:10','fotos.*'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
    }
    private function guardarFotos(Request $q, int $id, string $tipo): void
    {
        $rutas=[];
        try {
            DB::transaction(function () use ($q,$id,$tipo,&$rutas) {
                foreach ($q->file('fotos',[]) as $foto) {
                    $ruta=$foto->store('mantenimiento','local');
                    if (!$ruta) throw new \RuntimeException('No se pudo guardar la foto.');
                    $rutas[]=$ruta;
                    DB::table('mantenimiento_fotos')->insert(['orden_id'=>$id,'tipo'=>$tipo,'ruta'=>$ruta,'autor_id'=>$q->user()->id,'autor_nombre'=>$q->user()->name,'created_at'=>now(),'updated_at'=>now()]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($rutas);
            report($e);
            session()->flash('warning','El hallazgo quedó guardado, pero no se pudieron guardar las fotos. Podés agregarlas desde el seguimiento.');
        }
    }
    public function foto(Request $q, int $id, int $foto)
    {
        $this->buscarOrden($q,$id);
        $f=DB::table('mantenimiento_fotos')->where('orden_id',$id)->where('id',$foto)->first();
        abort_unless($f && Storage::disk('local')->exists($f->ruta),404);
        return Storage::disk('local')->response($f->ruta,null,['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
    private function avisar(Request $q, int $id, $respuesta)
    {
        $o=$this->buscarOrden($q,$id);
        if (!$o->responsable_id) return $respuesta;
        $u=User::where('company_id',$this->empresa($q))->where('activo',true)->find($o->responsable_id);
        if (!$u || !filter_var($u->email,FILTER_VALIDATE_EMAIL)) return $respuesta->with('warning','El hallazgo quedó guardado. El responsable no tiene un correo válido.');
        try {
            $autor=User::where('company_id',$this->empresa($q))->find($o->autor_id);
            $cc=collect(config('hallazgos.copias',[]))->push($autor?->email)->filter(fn($v)=>filter_var($v,FILTER_VALIDATE_EMAIL))->map(fn($v)=>strtolower(trim($v)))->reject(fn($v)=>strcasecmp($v,$u->email)===0)->unique()->values()->all();
            Mail::to($u->email)->cc($cc)->send(new MantenimientoAsignado($o,$u->name));
            if (in_array(config('mail.default'),['log','array'],true)) return $respuesta->with('warning','El hallazgo quedó guardado. El correo está en modo de prueba; no se envió a los destinatarios.');
            return $respuesta;
        } catch (\Throwable $e) {
            report($e);
            return $respuesta->with('warning','El hallazgo quedó guardado, pero no se pudo enviar el correo. No vuelvas a cargarlo.');
        }
    }

    public function exportar(Request $q)
    {
        $tareas=$this->tareas($q);
        return response()->streamDownload(function () use ($tareas) {
            $f=fopen('php://output','w'); fwrite($f,"\xEF\xBB\xBF");
            fputcsv($f,['Máquina','Descripción','Sector','Grado','Tarea','Frecuencia días','Última intervención','Próxima fecha','Situación'],';');
            foreach ($tareas as $t) { $r=[$t->numero,$t->maquina_nombre,$t->sector,$t->grado,$t->nombre,$t->frecuencia_dias,$t->ultima,$t->prevision['fecha'],Estado::ETIQUETAS[$t->prevision['estado']]]; fputcsv($f,array_map(fn ($v) => preg_match('/^[\s]*[=+@-]/u',(string)$v) ? "'".$v : $v,$r),';'); } fclose($f);
        },'mantenimiento-preventivo.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
