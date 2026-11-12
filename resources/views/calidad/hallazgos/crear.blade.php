@extends('layouts.app')
@section('content')
<h1>Nuevo hallazgo de calidad</h1><p>Podés asociarlo a un plan o chasis, o dejarlo como un hallazgo general de planta.</p>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="card card-body" method="post" action="{{ route('hallazgos.store') }}" enctype="multipart/form-data">@csrf
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Tipo</label><select name="tipo" class="form-select">@foreach(\App\Models\HallazgoCalidad::TIPOS as $v=>$n)<option value="{{ $v }}" @selected(old('tipo')===$v)>{{ $n }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Origen</label><select name="origen" class="form-select">@foreach(\App\Models\HallazgoCalidad::ORIGENES as $n)<option @selected(old('origen')===$n)>{{ $n }}</option>@endforeach</select></div>
<div class="col-md-5"><label class="form-label">Responsable de la acción</label><select name="responsable_id" class="form-select" required><option value="">Seleccionar responsable</option>@foreach($usuarios as $u)<option value="{{ $u->id }}" @selected(old('responsable_id')==$u->id)>{{ $u->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">#Plan (opcional)</label><input type="number" min="1" name="numero_plan" class="form-control" value="{{ old('numero_plan',request('plan')) }}"></div>
<div class="col-md-6"><label class="form-label">Chasis (opcional)</label><input name="chasis" class="form-control" value="{{ old('chasis',request('chasis')) }}"></div>
<div class="col-12"><label class="form-label">Título / punto detectado</label><input required maxlength="200" name="titulo" class="form-control" value="{{ old('titulo') }}"></div>
<div class="col-12"><label class="form-label">Descripción del hallazgo</label><textarea required name="descripcion" rows="4" class="form-control">{{ old('descripcion') }}</textarea></div>
<div class="col-12"><label class="form-label">Foto opcional (JPG, PNG o WEBP, hasta 5 MB)</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control"></div>
<div class="col-12"><button class="btn btn-success">Registrar hallazgo</button> <a href="{{ route('hallazgos.index') }}" class="btn btn-outline-secondary">Cancelar</a></div>
</div></form>
@endsection
