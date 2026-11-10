<?php
namespace App\Http\Controllers;
use App\Models\PlanProduccion;
use App\Models\EtapaProduccion;
use App\Services\EstimadorEntrega;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TableroProduccionController extends Controller {
    private function fecha($fecha): ?CarbonImmutable {
        return $fecha ? CarbonImmutable::parse(CarbonImmutable::parse($fecha)->format('Y-m-d'),EstimadorEntrega::ZONA)->startOfDay() : null;
    }
    private function barra($inicio,$fin,CarbonImmutable $desde,CarbonImmutable $hasta,bool $enCurso=false, float $pxDia=26): array {
        $inicio=$this->fecha($inicio); $fin=$this->fecha($fin);
        if(!$inicio && !$fin) return ['visible'=>false,'texto'=>'Sin fechas'];
        if($inicio && $fin && $fin->lt($inicio)) return ['visible'=>false,'texto'=>'Fechas invertidas: revisar'];
        $punto=!$inicio || !$fin;
        $inicio ??= $fin; $fin ??= $inicio;
        $texto=$punto ? $inicio->format('d/m/Y').' · fecha puntual' : $inicio->format('d/m/Y').' al '.$fin->format('d/m/Y');
        if($enCurso) $texto.=' · en curso';
        if($fin->lt($desde) || $inicio->gt($hasta))return ['visible'=>false,'texto'=>$texto.' · fuera del período'];
        $a=$inicio->max($desde);$z=$fin->min($hasta);
        return ['visible'=>true,'texto'=>$texto,'x'=>(int)$desde->diffInDays($a)*$pxDia,
            'ancho'=>((int)$a->diffInDays($z)+1)*$pxDia,'punto'=>$punto,'en_curso'=>$enCurso];
    }
    private function real($avances,$desde,$hasta,$hoy,float $pxDia=26): array {
        $activos=$avances->whereIn('estado',['en_proceso','completada']);
        $inicio=$activos->pluck('fecha_inicio')->filter()->sort()->first();
        $enCurso=$activos->contains('estado','en_proceso');
        $fin=$enCurso && $inicio ? $hoy : $activos->pluck('fecha_fin')->filter()->sort()->last();
        return $this->barra($inicio,$fin,$desde,$hasta,$enCurso,$pxDia);
    }
    public function index(Request $r) {
        $vistaGantt = $r->routeIs('gantt.sectores');
        $hoy=CarbonImmutable::now(EstimadorEntrega::ZONA)->startOfDay();
        $r->merge(['desde'=>$r->input('desde') ?: $hoy->startOfMonth()->toDateString(),
            'hasta'=>$r->input('hasta') ?: $hoy->addMonthsNoOverflow(2)->endOfMonth()->toDateString()]);
        $filtros=$r->validate(['estado'=>['nullable','in:abierto,cerrado,cancelado'],
            'sector_gantt'=>['nullable',\Illuminate\Validation\Rule::in(\App\Services\SectoresProduccion::CODIGOS)],
            'mostrar_fuera_periodo'=>['nullable','boolean'],
            'plan_gantt'=>['nullable','integer','min:1'],
            'numero_plan_gantt'=>['nullable','integer','min:1','max:4294967295'],
            'situacion_gantt'=>['nullable','in:sin_planificar,coincidencias,pendiente,en_proceso,completada'],
            'desde'=>['required','date'],'hasta'=>['required','date','after_or_equal:desde'],'page'=>['nullable','integer','min:1']]);
        $escala = $vistaGantt ? ($r->validate(['escala' => ['nullable', 'in:semana,mes,anio']])['escala'] ?? 'semana') : null;
        $pxDia = match ($escala) {
            'semana' => 14,
            'mes' => 4,
            'anio' => 0.8,
            default => 26,
        };
        if(!$r->has('estado'))$filtros['estado']='abierto';
        $desde=$this->fecha($filtros['desde']);$hasta=$this->fecha($filtros['hasta']);
        $maxDias = $vistaGantt ? 3660 : 366;
        if ($desde->diffInDays($hasta) >= $maxDias) {
            throw ValidationException::withMessages(['hasta' => "El Gantt permite hasta $maxDias días por consulta."]);
        }
        if ($vistaGantt) {
            $pxDia = max($pxDia, 600 / ((int) $desde->diffInDays($hasta) + 1));
        }
        $relaciones = ['avances.etapa', 'planProductos.producto'];
        if (!$vistaGantt) {
            $filtros += $r->validate([
                'numero_plan' => ['nullable','integer','min:1','max:4294967295'],
                'chasis' => ['nullable','string','max:100'],
                'serie' => ['nullable','string','max:100'],
                'cliente' => ['nullable','string','max:255'],
                'cliente_id' => ['nullable','integer','min:1'],
                'buscar_producto' => ['nullable','string','max:255'],
                'producto_id' => ['nullable','integer','min:1'],
                'etapa' => ['nullable','string','max:100'],
                'comercial' => ['nullable','in:stock,con_cliente'],
                'mostrar_entregadas' => ['nullable','boolean'],
            ]);
            $relaciones[] = 'planProductos.unidadesProduccion.cliente';
            $relaciones[] = 'planProductos.unidadesProduccion.avances.etapa';
            $relaciones[] = 'planProductos.unidadesProduccion.asignaciones.detalle.venta.cliente';
        }
        $planesTodos = PlanProduccion::with($relaciones)
            ->when(!empty($filtros['estado']),fn($q)=>$q->where('estado',$filtros['estado']))
            ->orderByRaw('fecha_planificada IS NULL')->orderBy('fecha_planificada')->orderBy('numero')->get();
        $etapas=EtapaProduccion::where('activo',true)->orderBy('orden')->get();
        if (!$vistaGantt) {
            $estimador=new EstimadorEntrega;
            $clientes = \App\Models\Cliente::orderBy('nombre')->get(['id','nombre','CodSistema','cuit']);
            $productos = \App\Models\Producto::with(['familia','tipo'])->orderBy('nombre')->get();
            $textoProducto = trim($filtros['buscar_producto'] ?? '');
            $idsProducto = $textoProducto !== '' && empty($filtros['producto_id'])
                ? \App\Models\Producto::where('nombre', 'like', '%'.$textoProducto.'%')
                    ->orWhere('codigo', 'like', '%'.$textoProducto.'%')->pluck('id')->flip()
                : null;
            $conteos=[];
            $unidadesVencidas = collect();
            $entregasEnRiesgo = collect();
            $etapaElegida = $filtros['etapa'] ?? null;
            $filtraUnidades = collect(['chasis','serie','cliente','cliente_id','buscar_producto','producto_id','comercial','etapa'])
                ->contains(fn ($clave) => filled($filtros[$clave] ?? null));
            $coincide = fn ($valor, $clave) => blank($filtros[$clave] ?? null)
                || mb_stripos((string) $valor, $filtros[$clave]) !== false;
            $numeroPlan = $filtros['numero_plan'] ?? null;
            $planesBuscados = $numeroPlan ? $planesTodos->where('numero', (int) $numeroPlan) : $planesTodos;
            $totales=['planes'=>$planesBuscados->count(),'unidades'=>0,'con_venta'=>0,'reservadas'=>0,'asignadas'=>0,'demoras'=>0,'sin_unidades'=>0];
            foreach($planesBuscados as $plan){
                $unidades=collect();$ventas=['reservadas'=>0,'asignadas'=>0,'total'=>0];$estados=[];
                foreach($plan->planProductos as $linea){
                    $linea->setRelation('plan',$plan);
                    foreach($linea->unidadesProduccion as $unidad){
                        $unidad->setRelation('planProducto',$linea);
                        $eta=$estimador->calcular($unidad);$unidad->setAttribute('estimacion_entrega',$eta);
                        if ((!empty($filtros['producto_id']) && (int) $linea->producto_id !== (int) $filtros['producto_id'])
                            || ($idsProducto !== null && !$idsProducto->has($linea->producto_id))) {
                            continue;
                        }
                        $nombre=$eta['excluida'] ? $eta['texto'] : $eta['etapa'];
                        $asignacion=$unidad->asignaciones->filter(fn($a)=>in_array($a->estado,['reservada','asignada']) && $a->detalle?->venta && $a->detalle->venta->estado!=='anulada')->sortByDesc('id')->first();
                        $cliente = $asignacion?->detalle?->venta?->cliente ?? $unidad->cliente;
                        $comercial = $filtros['comercial'] ?? null;
                        if (($nombre === 'Entregada' && empty($filtros['mostrar_entregadas']))
                            || !$coincide($unidad->chasis, 'chasis')
                            || !$coincide($unidad->serie, 'serie')
                            || (!empty($filtros['cliente_id'])
                                ? (int) $cliente?->id !== (int) $filtros['cliente_id']
                                : (!$coincide($cliente?->nombre, 'cliente') && !$coincide($cliente?->CodSistema, 'cliente') && !$coincide($cliente?->cuit, 'cliente')))
                            || ($comercial === 'stock' && ($eta['excluida'] || $asignacion || $cliente))
                            || ($comercial === 'con_cliente' && !$asignacion && !$cliente)) {
                            continue;
                        }
                        $conteos[$nombre]=($conteos[$nombre]??0)+1;
                        if ($etapaElegida && $nombre !== $etapaElegida) {
                            continue;
                        }
                        $estados[$nombre]=($estados[$nombre]??0)+1;
                        if($asignacion){$ventas[$asignacion->estado==='reservada'?'reservadas':'asignadas']++;$ventas['total']++;}
                        $totales['demoras']+=(int)!empty($eta['demoras_activas']);
                        if ($eta['vencida']) {
                            $unidadesVencidas->push($unidad);
                        }
                        $venta = $asignacion?->detalle?->venta;
                        $pactada = $venta?->fecha_entrega_pactada?->toDateString();
                        if (!$eta['excluida'] && !$unidad->avances->contains(fn ($avance) => $avance->etapa?->codigo === 'OK_CALIDAD' && $avance->estado === 'completada') && $venta?->estado === 'confirmada' && $pactada
                            && ($pactada < $hoy->toDateString() || ($eta['fecha'] && $eta['fecha'] > $pactada))) {
                            $entregasEnRiesgo->push([
                                'unidad' => $unidad,
                                'venta' => $venta,
                                'vencida' => $pactada < $hoy->toDateString(),
                            ]);
                        }
                        $unidades->push($unidad);
                    }
                }
                $totales['unidades']+=$unidades->count();$totales['con_venta']+=$ventas['total'];
                $totales['reservadas']+=$ventas['reservadas'];$totales['asignadas']+=$ventas['asignadas'];
                if($unidades->isEmpty())$totales['sin_unidades']++;
                $general=$plan->avances->whereIn('estado',['en_proceso','completada'])->sortByDesc(fn($a)=>$a->etapa?->orden??0)->first();
                $descripcion=$plan->observaciones ?: $plan->planProductos->map(fn($l)=>($l->producto?->nombre??'Producto no disponible').' × '.number_format((float)$l->cantidad,0,',','.'))->implode(' · ');
                $plan->setAttribute('tablero',['unidades'=>$unidades->count(),'ventas'=>$ventas,'estados'=>$estados,
                    'coincidencias'=>$filtraUnidades ? $unidades : collect(),
                    'general'=>$general?->etapa?->nombre ?? 'Planificado','descripcion'=>$descripcion ?: 'Sin descripción ni productos']);
            }
            $tarjetas=[];
            foreach($etapas as $etapa){
                if(isset($conteos[$etapa->nombre])){$tarjetas[]=['nombre'=>$etapa->nombre,'cantidad'=>$conteos[$etapa->nombre],'orden'=>$etapa->orden];unset($conteos[$etapa->nombre]);}
            }
            foreach($conteos as $nombre=>$cantidad)$tarjetas[]=['nombre'=>$nombre,'cantidad'=>$cantidad,'orden'=>99];
            $planesVisibles = $planesBuscados->filter(fn ($plan) => $plan->tablero['unidades'] > 0
                || (!$filtraUnidades && ($numeroPlan || $plan->planProductos->every(fn ($linea) => $linea->unidadesProduccion->isEmpty()))));
            $pagina=max(1,$r->integer('page',1));
            $planes=new LengthAwarePaginator($planesVisibles->forPage($pagina,10)->values(),$planesVisibles->count(),10,$pagina,
                ['path'=>route('dashboard'),'query'=>$r->query()]);
            $dias=collect();$meses=[];
            for($dia=$desde;$dia->lte($hasta);$dia=$dia->addDay()){
                $dias->push(['texto'=>$dia->format('d'),'fecha'=>$dia->format('d/m/Y'),'fin_semana'=>$dia->isWeekend(),'hoy'=>$dia->isSameDay($hoy)]);
                $mes=$dia->format('Y-m');
                if(!isset($meses[$mes]))$meses[$mes]=['nombre'=>$dia->locale('es')->translatedFormat('F Y'),'dias'=>0];
                $meses[$mes]['dias']++;
            }
            $gantt=[];
            foreach($planes as $plan){
                $unidades=$plan->planProductos->flatMap(fn($l)=>$l->unidadesProduccion);
                $avances=$plan->avances->concat($unidades->flatMap(fn($u)=>$u->avances));
                $pendientesDisponibilidad=$unidades->filter(fn($u)=>!$u->estimacion_entrega['excluida']);
                $disponibles=$pendientesDisponibilidad->filter(fn($u)=>$u->estimacion_entrega['fecha']);
                $sinEstimacion=$pendientesDisponibilidad->count()-$disponibles->count();
                $estimada=$disponibles->map(fn($u)=>$u->estimacion_entrega['fecha'])->sort()->last();
                $detalle=[];
                foreach(\App\Services\SectoresProduccion::ordenar($etapas->where('alcance','plan')->whereIn('codigo',\App\Services\SectoresProduccion::CODIGOS)) as $etapa){
                    $avance=$plan->avances->firstWhere('etapa_id',$etapa->id);
                    $detalle[]=['nombre'=>$etapa->nombre.' · plan','url'=>route('planes.avances',$plan),
                        'planificada'=>$this->barra($avance?->fecha_inicio_planificada,$avance?->fecha_fin_planificada,$desde,$hasta),
                        'real'=>$this->real(collect($avance?[$avance]:[]),$desde,$hasta,$hoy)];
                }
                foreach($unidades as $unidad){
                    $actual=$unidad->avances->whereIn('estado',['en_proceso','completada'])->sortByDesc(fn($a)=>$a->etapa?->orden??0)->first();
                    $detalle[]=['nombre'=>'Chasis '.($unidad->chasis ?: 'sin cargar (#'.$unidad->id.')').' · '.$unidad->estimacion_entrega['etapa'],
                        'url'=>route('planes.productos.unidades.avances',[$plan,$unidad->planProducto,$unidad]),
                        'planificada'=>$this->barra($actual?->fecha_inicio_planificada,$actual?->fecha_fin_planificada,$desde,$hasta),
                        'real'=>$this->real(collect($actual?[$actual]:[]),$desde,$hasta,$hoy)];
                }
                $estimacionPlan=$sinEstimacion ? ['visible'=>false,'texto'=>'Plazo sin definir en '.$sinEstimacion.' unidades'] : $this->barra($estimada,null,$desde,$hasta);
                if($pendientesDisponibilidad->isEmpty())$estimacionPlan=['visible'=>false,'texto'=>$unidades->isEmpty() ? 'Sin unidades generadas' : 'Sin unidades pendientes'];
                $gantt[$plan->id]=['planificada'=>$this->barra($plan->fecha_planificada,$plan->fecha_fin_planificada,$desde,$hasta),
                    'real'=>$this->real($avances,$desde,$hasta,$hoy),'estimada'=>$estimacionPlan,'detalle'=>$detalle];
            }
        }
        // Cada etapa compara todos los planes filtrados, sin depender de la página.
        $sectoresGantt=\App\Services\SectoresProduccion::ordenar($etapas->where('alcance','plan')->whereIn('codigo',\App\Services\SectoresProduccion::CODIGOS));
        if (empty($filtros['numero_plan_gantt']) && !empty($filtros['plan_gantt'])) {
            $filtros['numero_plan_gantt'] = $planesTodos->firstWhere('id', $filtros['plan_gantt'])?->numero;
        }
        $ganttEtapas=[];
        foreach($sectoresGantt as $etapa){
            if(!empty($filtros['sector_gantt']) && $etapa->codigo!==$filtros['sector_gantt'])continue;
            $filas=[];
            foreach($planesTodos as $plan){
                $avance=$plan->avances->firstWhere('etapa_id',$etapa->id);
                $inicio=$avance?->fecha_inicio_planificada;
                $fin=$avance?->fecha_fin_planificada;
                $aviso= !$inicio && !$fin ? 'Sin planificar' : (!$inicio || !$fin ? 'Planificación incompleta' : ($fin->lt($inicio) ? 'Fechas invertidas: revisar' : null));
                $filas[]=['plan'=>$plan,'avance'=>$avance,'inicio'=>$inicio,'fin'=>$fin,'aviso'=>$aviso,'coincidencias'=>[],
                    'planificada'=>$this->barra($inicio,$fin,$desde,$hasta,false,$pxDia),
                    'real'=>$this->real(collect($avance?[$avance]:[]),$desde,$hasta,$hoy,$pxDia)];
            }
            // La comparación es por día visible; indica coincidencia, no falta de capacidad.
            foreach($filas as $i=>$fila){
                if($fila['aviso'] || $fila['plan']->estado==='cancelado')continue;
                foreach($filas as $j=>$otra){
                    if($j<=$i || $otra['aviso'] || $otra['plan']->estado==='cancelado')continue;
                    $a=$this->fecha($fila['inicio'])->max($this->fecha($otra['inicio']))->max($desde);
                    $z=$this->fecha($fila['fin'])->min($this->fecha($otra['fin']))->min($hasta);
                    if($a->lte($z)){
                        $filas[$i]['coincidencias'][]=$otra['plan']->numero;
                        $filas[$j]['coincidencias'][]=$fila['plan']->numero;
                    }
                }
            }
            // Filtrar después de detectar coincidencias conserva los otros planes como referencia.
            $filas=array_values(array_filter($filas,function($fila)use($filtros){
                if (!empty($filtros['numero_plan_gantt'])) {
                    if ((int) $fila['plan']->numero !== (int) $filtros['numero_plan_gantt']) return false;
                } elseif (!empty($filtros['plan_gantt']) && (int) $fila['plan']->id !== (int) $filtros['plan_gantt']) {
                    return false;
                }
                $situacion=$filtros['situacion_gantt'] ?? '';
                if($situacion==='sin_planificar')return $fila['aviso']!==null;
                if (empty($filtros['mostrar_fuera_periodo']) && !$fila['planificada']['visible'] && !$fila['real']['visible']) {
                    return false;
                }
                if($situacion==='coincidencias')return !empty($fila['coincidencias']);
                return !$situacion || ($fila['avance']?->estado ?? 'pendiente')===$situacion;
            }));
            if($filas)$ganttEtapas[]=['nombre'=>$etapa->nombre,'filas'=>$filas];
        }
        $ancho = ((int) $desde->diffInDays($hasta) + 1) * $pxDia;
        $hoyX = $hoy->betweenIncluded($desde, $hasta) ? (int) $desde->diffInDays($hoy) * $pxDia : null;
        if ($vistaGantt) {
            $columnas = [];
            for ($inicio = $desde; $inicio->lte($hasta); $inicio = $fin->addDay()) {
                $fin = match ($escala) {
                    'semana' => $inicio->endOfWeek(CarbonImmutable::SUNDAY),
                    'mes' => $inicio->endOfMonth(),
                    'anio' => $inicio->endOfYear(),
                };
                $fin = $fin->startOfDay()->min($hasta);
                $columnas[] = [
                    'nombre' => match ($escala) {
                        'semana' => 'Sem. '.$inicio->isoWeek().' · '.$inicio->isoWeekYear(),
                        'mes' => $inicio->locale('es')->translatedFormat('M Y'),
                        'anio' => $inicio->format('Y'),
                    },
                    'desde' => $inicio->format('d/m/Y'),
                    'hasta' => $fin->format('d/m/Y'),
                    'ancho' => ((int) $inicio->diffInDays($fin) + 1) * $pxDia,
                ];
            }
            return view('tablero.gantt', compact('filtros', 'escala', 'columnas', 'ancho', 'hoyX', 'ganttEtapas', 'sectoresGantt'));
        }
        $mesUe = $r->validate(['mes_ue' => ['nullable', 'date_format:Y-m']])['mes_ue'] ?? $hoy->format('Y-m');
        $pesos = \Illuminate\Support\Facades\DB::table('producto_plazos_entrega')->whereNotNull('ue')->get()
            ->keyBy(fn ($peso) => $peso->producto_id.':'.$peso->etapa_id);
        $resumenUe = collect();
        foreach ($planesTodos->where('estado', '!=', 'cancelado') as $plan) {
            foreach ($plan->planProductos as $linea) {
                foreach ($plan->avances as $avance) {
                    $this->sumarUe($resumenUe, $pesos, $mesUe, $avance, $linea->producto_id, (float) $linea->cantidad);
                }
                foreach ($linea->unidadesProduccion->where('estado', '!=', 'cancelada') as $unidad) {
                    foreach ($unidad->avances as $avance) {
                        $this->sumarUe($resumenUe, $pesos, $mesUe, $avance, $linea->producto_id, 1);
                    }
                }
            }
        }
        $resumenUe = $resumenUe->sortBy('orden');
        return view('dashboard',compact('planes','filtros','clientes','productos','totales','tarjetas','unidadesVencidas','entregasEnRiesgo','mesUe','resumenUe','dias','meses','gantt','ancho','hoyX','hoy','ganttEtapas','sectoresGantt'));
    }

    private function sumarUe($resumen, $pesos, string $mes, $avance, int $producto, float $cantidad): void
    {
        if ($avance->estado !== 'completada' || $avance->fecha_fin?->format('Y-m') !== $mes || !$avance->etapa) {
            return;
        }

        $etapa = $avance->etapa;
        $peso = $pesos->get($producto.':'.$etapa->id);
        $fila = $resumen->get($etapa->id, ['nombre' => $etapa->nombre, 'orden' => $etapa->orden, 'ue' => 0, 'sin_ue' => 0]);
        if ($peso) {
            $fila['ue'] += $cantidad * (float) $peso->ue;
        } else {
            $fila['sin_ue'] += $cantidad;
        }
        $resumen->put($etapa->id, $fila);
    }
}
