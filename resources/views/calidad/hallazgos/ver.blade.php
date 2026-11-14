@extends('layouts.app')
@section('content')
    @if(session('warning'))<div class="alert alert-warning" role="alert">{{ session('warning') }}</div>@endif
    <div class="d-flex justify-content-between mb-3">
        <div>
            <h1>{{ $h->tipo }}-{{ $h->numero }} · {{ $h->titulo }}</h1><span
                class="badge bg-primary">{{ \App\Models\HallazgoCalidad::ESTADOS[$h->estado] }}</span>
        </div><a href="{{ route('hallazgos.index') }}" class="btn btn-outline-secondary align-self-start">Volver</a>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="card card-body mb-3">
        <div class="row g-3">
            <div class="col-md-4"><strong>Registró</strong>
                <div>{{ $h->autor?->name }} · {{ $h->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="col-md-4"><strong>Responsable</strong>
                <div>{{ $h->responsable?->name }}</div>
            </div>
            <div class="col-md-4"><strong>Origen</strong>
                <div>{{ $h->origen }}</div>
            </div>
            <div class="col-12"><strong>Referencia</strong>
                <div>{{ $h->plan ? 'Plan #' . $h->plan->numero : 'General de planta' }}
                    {{ $h->unidad ? ' · Chasis ' . $h->unidad->chasis : '' }}</div>
            </div>
            <div class="col-12" style="white-space:pre-wrap">{{ $h->descripcion }}</div>
        </div>
    </div>
    @if ($h->accion)
        <div class="card card-body mb-3">
            <h4>Acción propuesta</h4>
            <p style="white-space:pre-wrap">Causa: {{ $h->causa }}<br>Contención:
                {{ $h->contencion ?: '—' }}<br>Acción: {{ $h->accion }}</p><strong>Fecha compromiso:
                {{ $h->fecha_compromiso?->format('d/m/Y') }}</strong>
        </div>
    @endif
    @if ($h->criterio_eficacia || $h->resultado_eficacia)
        <div class="card card-body mb-3">
            <h4>Verificación de eficacia</h4>
            <p style="white-space:pre-wrap"><strong>Criterio:</strong> {{ $h->criterio_eficacia ?: 'Sin registrar' }}</p>
            <p><strong>Fecha prevista:</strong> {{ $h->fecha_verificacion_prevista?->format('d/m/Y') ?: 'Sin definir' }}</p>
            @if ($h->resultado_eficacia)
                <p style="white-space:pre-wrap"><strong>Resultado:</strong> {{ $h->resultado_eficacia }}</p>
                <p>Verificó {{ $h->verificador?->name }} · {{ $h->verificado_en?->format('d/m/Y H:i') }}</p>
            @endif
        </div>
    @endif
    @php($esResponsable = (int) auth()->id() === (int) $h->responsable_id)
    @php($esAutor = (int) auth()->id() === (int) $h->autor_id)
    @if (($esResponsable && in_array($h->estado, ['abierto', 'en_accion'])) || ($esAutor && $h->estado === 'por_verificar'))
        <form class="card card-body mb-3" method="post" action="{{ route('hallazgos.actualizar', $h) }}"
            enctype="multipart/form-data">@csrf
            <input type="hidden" name="version" value="{{ $h->historial->max('id') }}">
            <h4>{{ $h->estado === 'por_verificar' ? 'Verificar resultado' : 'Registrar seguimiento' }}</h4>
            <label class="form-label">Acción</label><select name="accion_form" id="hallazgo-accion"
                class="form-select mb-3">
                @if ($esResponsable && in_array($h->estado, ['abierto', 'en_accion']))
                    <option value="responder" @selected(old('accion_form') === 'responder')>Responder / actualizar acción propuesta</option>
                    @if ($h->estado === 'en_accion')
                        <option value="terminar" @selected(old('accion_form') === 'terminar')>Acción realizada · enviar a verificación
                        </option>
                    @endif
                @else<option value="validar">Eficacia comprobada · cerrar hallazgo</option>
                    <option value="devolver" @selected(old('accion_form') === 'devolver')>No eficaz / requiere ajustes · devolver</option>
                @endif
            </select>
            @if ($esResponsable)
                <div id="hallazgo-respuesta">
                    <div class="row g-3 mb-3">
                        <div class="col-12"><label class="form-label">Causa</label>
                            <textarea class="form-control" name="causa">{{ old('causa', $h->causa) }}</textarea>
                        </div>
                        <div class="col-12"><label class="form-label">Contención inmediata (opcional)</label>
                            <textarea class="form-control" name="contencion">{{ old('contencion', $h->contencion) }}</textarea>
                        </div>
                        <div class="col-12"><label class="form-label">Acción a realizar</label>
                            <textarea class="form-control" name="accion">{{ old('accion', $h->accion) }}</textarea>
                        </div>
                        <div class="col-md-4"><label class="form-label">Fecha compromiso</label><input type="date"
                                class="form-control" name="fecha_compromiso"
                                value="{{ old('fecha_compromiso', $h->fecha_compromiso?->format('Y-m-d')) }}"></div>
                    </div>
                </div>
            @endif
            @if ($esResponsable && $h->estado !== 'por_verificar')
                <div id="hallazgo-eficacia" class="mb-3">
                    <label class="form-label">Cómo se comprobará la eficacia *</label>
                    <textarea name="criterio_eficacia" class="form-control mb-3" maxlength="10000" placeholder="Ej.: inspeccionar las próximas 10 unidades y comprobar que no se repite el defecto.">{{ old('criterio_eficacia', $h->criterio_eficacia) }}</textarea>
                    <label class="form-label">Fecha prevista de verificación *</label>
                    <input type="date" name="fecha_verificacion_prevista" class="form-control" min="{{ today()->format('Y-m-d') }}" value="{{ old('fecha_verificacion_prevista', $h->fecha_verificacion_prevista?->format('Y-m-d')) }}">
                </div>
            @endif
            @if ($esAutor && $h->estado === 'por_verificar')
                <label class="form-label">Evidencia y resultado de la verificación *</label>
                <textarea name="resultado_eficacia" required maxlength="10000" rows="3" class="form-control mb-3" placeholder="Indicá qué se verificó, los registros consultados y el resultado obtenido.">{{ old('resultado_eficacia') }}</textarea>
                <p class="text-muted">Cerrá solamente si existe evidencia suficiente de eficacia. Podés adjuntar una foto como respaldo.</p>
            @endif
            <label class="form-label">Comentario / trabajo realizado *</label>
            <textarea required name="detalle" rows="3" class="form-control mb-3">{{ old('detalle') }}</textarea><label class="form-label">Foto opcional (JPG, PNG o WEBP, hasta 5 MB)</label><input
                type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control mb-3"><button
                class="btn btn-success align-self-start">Guardar seguimiento</button>
        </form>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const s = document.getElementById('hallazgo-accion'),
                    r = document.getElementById('hallazgo-respuesta');
                const eficacia = document.getElementById('hallazgo-eficacia');
                if (r) {
                    const actualizar = () => {
                        r.hidden = s.value !== 'responder';
                        r.querySelectorAll('textarea,input').forEach(e => e.disabled = r.hidden);
                        if (eficacia) {
                            eficacia.hidden = s.value !== 'terminar';
                            eficacia.querySelectorAll('textarea,input').forEach(e => { e.disabled = eficacia.hidden; e.required = !eficacia.hidden; });
                        }
                    };
                    s.addEventListener('change', actualizar);
                    actualizar();
                }
            });
        </script>
    @endif
    @if ($esAutor && in_array($h->estado, ['abierto', 'en_accion', 'por_verificar']))
        <details class="card card-body mb-3">
            <summary class="text-danger">Anular hallazgo</summary>
            <p class="mt-3">Para cargas equivocadas o duplicadas. Se conservarán el hallazgo, las fotos y el historial.
            </p>
            <form method="post" action="{{ route('hallazgos.actualizar', $h) }}">@csrf
                <input type="hidden" name="accion_form" value="anular"><input type="hidden" name="version"
                    value="{{ $h->historial->max('id') }}">
                <label for="motivo-anulacion" class="form-label">Motivo de la anulación</label>
                <textarea id="motivo-anulacion" name="detalle" required maxlength="10000" class="form-control mb-3" rows="2"></textarea>
                <button class="btn btn-outline-danger">Confirmar anulación</button>
            </form>
        </details>
    @endif
    <div class="card card-body">
        <h4>Historial</h4>
        @foreach ($h->historial as $m)
            <div class="border-bottom py-3"><strong>{{ $m->created_at->format('d/m/Y H:i') }} · {{ $m->usuario?->name }} ·
                    {{ \App\Models\HallazgoCalidad::ESTADOS[$m->estado_nuevo] }}</strong>
                <p style="white-space:pre-wrap">{{ $m->detalle }}</p>
                @if ($m->foto)
                    <a target="_blank" rel="noopener" href="{{ route('hallazgos.foto', [$h, $m]) }}">Ver foto adjunta</a>
                @endif
            </div>
        @endforeach
    </div>
@endsection
