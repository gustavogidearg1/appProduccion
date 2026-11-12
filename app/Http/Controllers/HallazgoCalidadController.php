<?php

namespace App\Http\Controllers;

use App\Models\{HallazgoCalidad as H, HallazgoMovimiento, PlanProduccion, UnidadProduccion, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\Rule;

class HallazgoCalidadController extends Controller
{
    private function consulta(Request $q)
    {
        abort_unless($q->user()->activo, 403);
        return H::query()->where('company_id', $q->user()->company_id);
    }

    public function index(Request $q)
    {
        $q->validate(['estado' => ['nullable', Rule::in(['pendientes', 'todos', ...array_keys(H::ESTADOS)])], 'buscar' => 'nullable|string|max:200']);
        $consulta = $this->consulta($q)->with(['responsable', 'plan', 'unidad']);
        $estado = $q->input('estado', 'pendientes');
        if ($estado === 'pendientes') $consulta->where('estado', '!=', 'cerrado');
        elseif ($estado !== 'todos') $consulta->where('estado', $estado);
        if ($q->filled('buscar')) {
            $texto = $q->string('buscar')->toString();
            $consulta->where(function ($c) use ($texto) {
                $c->where('titulo', 'like', "%$texto%")
                    ->orWhere('numero', $texto)
                    ->orWhereHas('unidad', fn ($u) => $u->where('chasis', 'like', "%$texto%"))
                    ->orWhereHas('plan', fn ($p) => $p->where('numero', $texto));
            });
        }
        if ($q->boolean('mios')) $consulta->where('responsable_id', $q->user()->id);
        if ($q->boolean('vencidos')) $consulta->whereIn('estado', ['abierto', 'en_accion'])->whereDate('fecha_compromiso', '<', today());
        $hallazgos = $consulta->latest('id')->paginate(25)->withQueryString();
        return view('calidad.hallazgos.index', compact('hallazgos', 'estado'));
    }

    public function create(Request $q)
    {
        $this->consulta($q);
        $usuarios = User::where('activo', true)->where('company_id', $q->user()->company_id)->orderBy('name')->get(['id', 'name']);
        return view('calidad.hallazgos.crear', compact('usuarios'));
    }

    public function store(Request $q)
    {
        $this->consulta($q);
        $d = $q->validate([
            'tipo' => ['required', Rule::in(array_keys(H::TIPOS))],
            'origen' => ['required', Rule::in(H::ORIGENES)],
            'titulo' => 'required|string|max:200', 'descripcion' => 'required|string|max:10000',
            'responsable_id' => 'required|integer', 'numero_plan' => 'nullable|integer|min:1',
            'chasis' => 'nullable|string|max:100', 'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        abort_unless(User::whereKey($d['responsable_id'])->where('activo', true)->where('company_id', $q->user()->company_id)->exists(), 422, 'Elegí un responsable activo de la empresa.');
        $plan = !empty($d['numero_plan']) ? PlanProduccion::where('numero', $d['numero_plan'])->first() : null;
        if (!empty($d['numero_plan']) && !$plan) return back()->withInput()->withErrors(['numero_plan' => 'No existe ese plan.']);
        $unidad = null;
        if (!empty($d['chasis'])) {
            $unidades = UnidadProduccion::where('chasis', $d['chasis'])->when($plan, fn ($c) => $c->whereHas('planProducto', fn ($p) => $p->where('plan_produccion_id', $plan->id)))->limit(2)->get();
            if ($unidades->count() !== 1) return back()->withInput()->withErrors(['chasis' => 'No se encontró un chasis único. Revisá el número e indicá el plan.']);
            $unidad = $unidades->first();
            $plan = $unidad->planProducto->plan;
        }
        $foto = $q->file('foto')?->store('hallazgos', 'local');
        try {
            $h = DB::transaction(function () use ($q, $d, $plan, $unidad, $foto) {
                $seq = DB::table('calidad_hallazgo_secuencias')->where('tipo', $d['tipo'])->lockForUpdate()->first();
                DB::table('calidad_hallazgo_secuencias')->where('tipo', $d['tipo'])->update(['ultimo' => $seq->ultimo + 1]);
                $h = H::create([
                    'tipo' => $d['tipo'], 'numero' => $seq->ultimo + 1, 'origen' => $d['origen'],
                    'titulo' => $d['titulo'], 'descripcion' => $d['descripcion'],
                    'autor_id' => $q->user()->id, 'responsable_id' => $d['responsable_id'], 'company_id' => $q->user()->company_id,
                    'plan_produccion_id' => $plan?->id, 'unidad_produccion_id' => $unidad?->id, 'estado' => 'abierto',
                ]);
                $h->historial()->create(['usuario_id' => $q->user()->id, 'accion' => 'registro', 'estado_nuevo' => 'abierto', 'detalle' => $d['descripcion'], 'foto' => $foto]);
                return $h;
            });
        } catch (\Throwable $e) { if ($foto) Storage::disk('local')->delete($foto); throw $e; }
        return redirect()->route('hallazgos.show', $h)->with('success', 'Hallazgo registrado y asignado.');
    }

    public function show(Request $q, int $hallazgo)
    {
        $h = $this->consulta($q)->with(['autor', 'responsable', 'plan', 'unidad', 'historial.usuario'])->findOrFail($hallazgo);
        return view('calidad.hallazgos.ver', compact('h'));
    }

    public function actualizar(Request $q, int $hallazgo)
    {
        $this->consulta($q)->findOrFail($hallazgo);
        $d = $q->validate([
            'accion_form' => ['required', Rule::in(['responder', 'terminar', 'validar', 'devolver'])],
            'version' => 'required|integer', 'detalle' => 'required|string|max:10000',
            'causa' => 'required_if:accion_form,responder|nullable|string|max:10000',
            'contencion' => 'nullable|string|max:10000',
            'accion' => 'required_if:accion_form,responder|nullable|string|max:10000',
            'fecha_compromiso' => 'required_if:accion_form,responder|nullable|date_format:Y-m-d|after_or_equal:today',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $foto = null;
        try {
            DB::transaction(function () use ($q, $hallazgo, $d, &$foto) {
                $h = $this->consulta($q)->lockForUpdate()->findOrFail($hallazgo);
                abort_unless((int) $h->historial()->max('id') === (int) $d['version'], 409, 'El hallazgo cambió. Recargá antes de guardar.');
                $accion = $d['accion_form'];
                $responsable = in_array($accion, ['responder', 'terminar'], true);
                abort_unless((int) ($responsable ? $h->responsable_id : $h->autor_id) === (int) $q->user()->id, 403);
                $permitidos = ['responder' => ['abierto', 'en_accion'], 'terminar' => ['en_accion'], 'validar' => ['por_verificar'], 'devolver' => ['por_verificar']];
                abort_unless(in_array($h->estado, $permitidos[$accion], true), 409, 'La acción no corresponde al estado actual.');
                $anterior = $h->estado;
                if ($accion === 'responder') $h->fill(collect($d)->only(['causa', 'contencion', 'accion', 'fecha_compromiso'])->all());
                $h->estado = ['responder' => 'en_accion', 'terminar' => 'por_verificar', 'validar' => 'cerrado', 'devolver' => 'en_accion'][$accion];
                $h->save();
                $foto = $q->file('foto')?->store('hallazgos', 'local');
                $detalle = $d['detalle'];
                if ($accion === 'responder') $detalle .= "\nCausa: {$d['causa']}\nContención: ".($d['contencion'] ?? '')."\nAcción: {$d['accion']}\nCompromiso: {$d['fecha_compromiso']}";
                $h->historial()->create(['usuario_id' => $q->user()->id, 'accion' => $accion, 'estado_anterior' => $anterior, 'estado_nuevo' => $h->estado, 'detalle' => $detalle, 'foto' => $foto]);
            });
        } catch (\Throwable $e) { if ($foto) Storage::disk('local')->delete($foto); throw $e; }
        return redirect()->route('hallazgos.show', $hallazgo)->with('success', 'Seguimiento guardado.');
    }

    public function foto(Request $q, int $hallazgo, int $movimiento)
    {
        $h = $this->consulta($q)->findOrFail($hallazgo);
        $m = $h->historial()->findOrFail($movimiento);
        abort_unless($m->foto && Storage::disk('local')->exists($m->foto), 404);
        return response()->file(Storage::disk('local')->path($m->foto), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
