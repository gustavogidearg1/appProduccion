<div class="gantt-timeline" style="width:{{ $ancho }}px">
@foreach (['planificada'=>'plan','real'=>'real','estimada'=>'estimate'] as $clave=>$clase)
@if (isset($fila[$clave]))
@php
    $barra=$fila[$clave];
@endphp
<div class="gantt-track" aria-label="{{ $clave }}: {{ $barra['texto'] }}">
@if ($barra['visible'])<span tabindex="0" role="img" aria-label="{{ $clave }}: {{ $barra['texto'] }}" class="gantt-bar {{ $clase }} {{ !empty($barra['punto']) ? 'point' : '' }} {{ !empty($barra['en_curso']) ? 'ongoing' : '' }}" title="{{ ucfirst($clave) }}: {{ $barra['texto'] }}" style="left:{{ $barra['x'] }}px;width:{{ $barra['ancho'] }}px"></span>
@else<span class="gantt-empty">{{ ucfirst($clave) }}: {{ $barra['texto'] }}</span>@endif
</div>
@endif
@endforeach
@if ($hoyX !== null)<div class="gantt-today" style="left:{{ $hoyX }}px"></div>@endif
</div>
