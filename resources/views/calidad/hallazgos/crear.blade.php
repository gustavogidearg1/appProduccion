@extends('layouts.app')
@section('content')
<h1>Nuevo hallazgo de calidad</h1><p>Podés asociarlo a un plan o chasis, o dejarlo como un hallazgo general de planta.</p>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="card card-body" method="post" action="{{ route('hallazgos.store') }}" enctype="multipart/form-data">@csrf
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Tipo *</label><select name="tipo" class="form-select">@foreach(\App\Models\HallazgoCalidad::TIPOS as $v=>$n)<option value="{{ $v }}" @selected(old('tipo')===$v)>{{ $n }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Origen *</label><select name="origen" class="form-select">@foreach(\App\Models\HallazgoCalidad::ORIGENES as $n)<option @selected(old('origen')===$n)>{{ $n }}</option>@endforeach</select></div>
<div class="col-md-5"><label for="responsable-nombre" class="form-label">Responsable de la acción</label>
<div class="input-group"><input id="responsable-nombre" class="form-control" readonly required placeholder="Seleccionar responsable" value="{{ $usuarios->firstWhere('id', old('responsable_id'))?->name }}"><input type="hidden" id="responsable-id" name="responsable_id" value="{{ old('responsable_id') }}">
<button type="button" id="buscar-responsable" class="btn btn-outline-primary" aria-label="Buscar responsable" title="Buscar responsable"><i class="bi bi-search" aria-hidden="true"></i></button></div>
<small class="text-muted">Se enviará el aviso al guardar el hallazgo.</small></div>
<div class="col-md-6"><label class="form-label">#Plan (opcional)</label><input type="number" min="1" name="numero_plan" class="form-control" value="{{ old('numero_plan',request('plan')) }}"></div>
<div class="col-md-6"><label class="form-label">Chasis (opcional)</label><input name="chasis" class="form-control" value="{{ old('chasis',request('chasis')) }}"></div>
<div class="col-12"><label class="form-label">Título / punto detectado</label><input required maxlength="200" name="titulo" class="form-control" value="{{ old('titulo') }}"></div>
<div class="col-12"><label class="form-label">Descripción del hallazgo</label><textarea required name="descripcion" rows="4" class="form-control">{{ old('descripcion') }}</textarea></div>
<div class="col-12"><label class="form-label">Foto opcional (JPG, PNG o WEBP, hasta 5 MB)</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control"></div>
<div class="col-12"><button class="btn btn-success">Registrar hallazgo</button> <a href="{{ route('hallazgos.index') }}" class="btn btn-outline-secondary">Cancelar</a></div>
</div></form>
<dialog id="modal-responsables" aria-labelledby="titulo-responsables" style="width:min(760px,95vw);border:1px solid #ddd;border-radius:10px;padding:0">
<div class="p-3"><div class="d-flex justify-content-between align-items-center mb-3"><h2 id="titulo-responsables" class="h5 mb-0">Buscar responsable</h2><button type="button" id="cerrar-responsables" class="btn btn-outline-secondary" aria-label="Cerrar buscador">Cerrar</button></div>
<label for="filtro-responsables" class="form-label">Nombre o correo electrónico</label><input id="filtro-responsables" type="search" class="form-control mb-3" placeholder="Escribí parte del nombre o correo">
<div style="max-height:55vh;overflow:auto"><table class="table table-hover"><thead><tr><th>Usuario</th><th>Correo</th><th></th></tr></thead><tbody>
@foreach($usuarios as $u)<tr data-usuario><td>{{ $u->name }}</td><td>{{ $u->email ?: 'Sin correo' }}</td><td><button type="button" class="btn btn-sm btn-success" data-id="{{ $u->id }}" data-nombre="{{ $u->name }}">Seleccionar</button></td></tr>@endforeach
</tbody></table><p id="sin-responsables" hidden>No se encontraron usuarios.</p></div></div></dialog>
<style>#modal-responsables::backdrop { background:rgba(0,0,0,.45); }</style>
<script>
(() => {
const modal = document.getElementById('modal-responsables'), filtro = document.getElementById('filtro-responsables');
const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
document.getElementById('buscar-responsable').addEventListener('click', () => { modal.showModal(); filtro.focus(); });
document.getElementById('cerrar-responsables').addEventListener('click', () => modal.close());
filtro.addEventListener('input', () => { let encontrados = 0; modal.querySelectorAll('[data-usuario]').forEach(fila => { fila.hidden = !normalizar(fila.textContent).includes(normalizar(filtro.value.trim())); if (!fila.hidden) encontrados++; }); document.getElementById('sin-responsables').hidden = encontrados > 0; });
modal.querySelectorAll('[data-id]').forEach(boton => boton.addEventListener('click', () => { document.getElementById('responsable-id').value = boton.dataset.id; document.getElementById('responsable-nombre').value = boton.dataset.nombre; modal.close(); document.getElementById('buscar-responsable').focus(); }));
})();
</script>
@endsection
