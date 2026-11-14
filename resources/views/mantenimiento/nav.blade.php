<div class="d-flex flex-wrap gap-2 mb-4">
<a class="btn btn-outline-primary" href="{{ route('mantenimiento.index') }}">Preventivo y máquinas</a>
<a class="btn btn-outline-primary" href="{{ route('mantenimiento.calendario') }}">Calendario</a>
<a class="btn btn-outline-primary" href="{{ route('mantenimiento.ordenes') }}">Hallazgos y órdenes</a>
<a class="btn btn-primary" href="{{ route('mantenimiento.ordenes.nueva') }}">Registrar hallazgo</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
