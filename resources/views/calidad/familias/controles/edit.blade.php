@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h1>Editar control de calidad</h1>

        <p class="text-muted mb-0">
            Familia: {{ $familia->nombre }}
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header">
            <h2 class="h5 mb-0">Datos del control</h2>
        </div>

        <div class="card-body">
            <form method="POST"
                  action="{{ route(
                    'calidad.familias.controles.update',
                    [$familia, $familiaControl]
                  ) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nombre del control</label>

                    <input type="text"
                           name="nombre"
                           class="form-control"
                           value="{{ old(
                               'nombre',
                               $familiaControl->control->nombre
                           ) }}"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Descripción o instrucción
                    </label>

                    <textarea name="descripcion"
                              class="form-control"
                              rows="3">{{ old(
                                  'descripcion',
                                  $familiaControl->control->descripcion
                              ) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Etapa de producción
                    </label>

                    <select name="etapa_produccion_id"
                            class="form-select"
                            required>
                        @foreach ($etapas as $etapa)
                            <option value="{{ $etapa->id }}"
                                @selected(
                                    old(
                                        'etapa_produccion_id',
                                        $familiaControl->etapa_produccion_id
                                    ) == $etapa->id
                                )>
                                {{ $etapa->orden }} -
                                {{ $etapa->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Tipo de respuesta
                    </label>

                    <select name="tipo_respuesta"
                            class="form-select"
                            required>
                        <option value="si_no"
                            @selected(old(
                                'tipo_respuesta',
                                $familiaControl->control->tipo_respuesta
                            ) === 'si_no')>
                            Sí / No
                        </option>

                        <option value="valor"
                            @selected(old(
                                'tipo_respuesta',
                                $familiaControl->control->tipo_respuesta
                            ) === 'valor')>
                            Valor o medida
                        </option>

                        <option value="texto"
                            @selected(old(
                                'tipo_respuesta',
                                $familiaControl->control->tipo_respuesta
                            ) === 'texto')>
                            Texto
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Respuesta esperada
                    </label>

                    <input type="text"
                           name="respuesta_esperada"
                           class="form-control"
                           value="{{ old(
                               'respuesta_esperada',
                               $familiaControl->respuesta_esperada
                           ) }}"
                           placeholder="Ejemplo: Sí, Rojo Case, dentro del rango">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            Unidad de medida
                        </label>

                        <input type="text"
                               name="unidad_medida"
                               class="form-control"
                               value="{{ old(
                                   'unidad_medida',
                                   $familiaControl->unidad_medida
                               ) }}"
                               placeholder="PSI, mm, kg">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Valor mínimo
                        </label>

                        <input type="number"
                               name="valor_minimo"
                               class="form-control"
                               step="0.001"
                               value="{{ old(
                                   'valor_minimo',
                                   $familiaControl->valor_minimo
                               ) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Valor máximo
                        </label>

                        <input type="number"
                               name="valor_maximo"
                               class="form-control"
                               step="0.001"
                               value="{{ old(
                                   'valor_maximo',
                                   $familiaControl->valor_maximo
                               ) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Orden dentro de la etapa
                    </label>

                    <input type="number"
                           name="orden"
                           class="form-control"
                           min="1"
                           value="{{ old(
                               'orden',
                               $familiaControl->orden
                           ) }}"
                           required>
                </div>

                <div class="form-check mb-2">
                    <input type="checkbox"
                           name="obligatorio"
                           value="1"
                           class="form-check-input"
                           id="obligatorio"
                           @checked(old(
                               'obligatorio',
                               $familiaControl->obligatorio
                           ))>

                    <label class="form-check-label"
                           for="obligatorio">
                        El control es obligatorio
                    </label>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox"
                           name="activo"
                           value="1"
                           class="form-check-input"
                           id="activo"
                           @checked(old(
                               'activo',
                               $familiaControl->activo
                           ))>

                    <label class="form-check-label"
                           for="activo">
                        Control activo
                    </label>
                </div>

                <button type="submit"
                        class="btn btn-primary">
                    Guardar cambios
                </button>

                <a href="{{ route(
                    'calidad.familias.controles.index',
                    $familia
                ) }}"
                   class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </form>
        </div>
    </div>

</div>
@endsection
