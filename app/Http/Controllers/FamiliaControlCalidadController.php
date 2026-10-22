<?php

namespace App\Http\Controllers;

use App\Models\ControlCalidad;
use App\Models\Familia;
use App\Models\FamiliaControlCalidad;
use App\Models\EtapaProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamiliaControlCalidadController extends Controller
{
    public function familias()
    {
        $familias = Familia::query()
            ->orderBy('nombre')
            ->get();

        return view(
            'calidad.index',
            compact('familias')
        );
    }

    public function index(Familia $familia)
    {
        $controlesFamilia = FamiliaControlCalidad::with([
            'control',
            'etapa',
        ])
            ->where('familia_id', $familia->id)
            ->orderBy('orden')
            ->get();

        return view(
            'calidad.familias.controles.index',
            compact('familia', 'controlesFamilia')
        );
    }

    public function create(Familia $familia)
    {
        $etapas = EtapaProduccion::query()
            ->where('alcance', 'unidad')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return view(
            'calidad.familias.controles.create',
            compact('familia', 'etapas')
        );
    }

    public function store(Request $request, Familia $familia)
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'tipo_respuesta' => [
                'required',
                'string',
                'in:si_no,valor,texto',
            ],

            'etapa_produccion_id' => [
                'required',
                'integer',
                'exists:etapas_produccion,id',
            ],

            'orden' => [
                'required',
                'integer',
                'min:1',
            ],

            'obligatorio' => [
                'nullable',
                'boolean',
            ],

            'respuesta_esperada' => [
                'nullable',
                'string',
            ],

            'unidad_medida' => [
                'nullable',
                'string',
                'max:50',
            ],

            'valor_minimo' => [
                'nullable',
                'numeric',
            ],

            'valor_maximo' => [
                'nullable',
                'numeric',
                'gte:valor_minimo',
            ],


        ]);

        DB::transaction(function () use ($datos, $request, $familia) {
            $control = ControlCalidad::create([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'tipo_respuesta' => $datos['tipo_respuesta'],
                'orden' => $datos['orden'],
                'obligatorio' => $request->boolean('obligatorio'),
                'activo' => true,
            ]);

            FamiliaControlCalidad::create([
                'familia_id' => $familia->id,
                'control_calidad_id' => $control->id,
                'etapa_produccion_id' => $datos['etapa_produccion_id'],
                'orden' => $datos['orden'],
                'obligatorio' => $request->boolean('obligatorio'),
                'activo' => true,
                'respuesta_esperada' => $datos['respuesta_esperada'] ?? null,
                'unidad_medida' => $datos['unidad_medida'] ?? null,
                'valor_minimo' => $datos['valor_minimo'] ?? null,
                'valor_maximo' => $datos['valor_maximo'] ?? null,
            ]);
        });

        return redirect()
            ->route('calidad.familias.controles.index', $familia)
            ->with('success', 'Control creado y asignado correctamente.');
    }

    public function edit(
        Familia $familia,
        FamiliaControlCalidad $familiaControl
    ) {
        abort_unless(
            $familiaControl->familia_id === $familia->id,
            404
        );

        $familiaControl->load([
            'control',
            'etapa',
        ]);

        $etapas = EtapaProduccion::query()
            ->where('alcance', 'unidad')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return view(
            'calidad.familias.controles.edit',
            compact(
                'familia',
                'familiaControl',
                'etapas'
            )
        );
    }

    public function update(
    Request $request,
    Familia $familia,
    FamiliaControlCalidad $familiaControl
) {
    abort_unless(
        $familiaControl->familia_id === $familia->id,
        404
    );

    $datos = $request->validate([
        'nombre' => [
            'required',
            'string',
            'max:255',
        ],

        'descripcion' => [
            'nullable',
            'string',
        ],

        'tipo_respuesta' => [
            'required',
            'string',
            'in:si_no,valor,texto',
        ],

        'etapa_produccion_id' => [
            'required',
            'integer',
            'exists:etapas_produccion,id',
        ],

        'respuesta_esperada' => [
            'nullable',
            'string',
        ],

        'unidad_medida' => [
            'nullable',
            'string',
            'max:50',
        ],

        'valor_minimo' => [
            'nullable',
            'numeric',
        ],

        'valor_maximo' => [
            'nullable',
            'numeric',
            'gte:valor_minimo',
        ],

        'orden' => [
            'required',
            'integer',
            'min:1',
        ],

        'obligatorio' => [
            'nullable',
            'boolean',
        ],

        'activo' => [
            'nullable',
            'boolean',
        ],
    ]);

    DB::transaction(function () use (
        $datos,
        $request,
        $familiaControl
    ) {
        $familiaControl->control->update([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'tipo_respuesta' => $datos['tipo_respuesta'],
            'orden' => $datos['orden'],
            'obligatorio' => $request->boolean('obligatorio'),
            'activo' => $request->boolean('activo'),
        ]);

        $familiaControl->update([
            'etapa_produccion_id' => $datos['etapa_produccion_id'],
            'respuesta_esperada' => $datos['respuesta_esperada'] ?? null,
            'unidad_medida' => $datos['unidad_medida'] ?? null,
            'valor_minimo' => $datos['valor_minimo'] ?? null,
            'valor_maximo' => $datos['valor_maximo'] ?? null,
            'orden' => $datos['orden'],
            'obligatorio' => $request->boolean('obligatorio'),
            'activo' => $request->boolean('activo'),
        ]);
    });

    return redirect()
        ->route('calidad.familias.controles.index', $familia)
        ->with('success', 'Control actualizado correctamente.');
}

    public function destroy(
        Familia $familia,
        FamiliaControlCalidad $familiaControl
    ) {
        abort_unless(
            $familiaControl->familia_id === $familia->id,
            404
        );

        $familiaControl->delete();

        return redirect()
            ->route('calidad.familias.controles.index', $familia)
            ->with('success', 'Control quitado de la familia.');
    }
}
