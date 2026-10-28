<div>{{ $estimacion['texto'] }}</div>
@if (!empty($estimacion['demoras_activas']))<div class="small text-warning-emphasis">Demora registrada · +{{ $estimacion['impacto'] }} días hábiles</div>@endif
@if (!empty($estimacion['vencida']))<div class="small text-warning-emphasis">Previsión original vencida</div>@endif
@if (!empty($estimacion['explicacion']))
<details class="small mt-1"><summary class="text-primary" style="cursor:pointer">¿Cómo se calculó?</summary>
<div class="border rounded bg-light p-2 mt-1" style="min-width:230px;max-width:420px">
@foreach ($estimacion['explicacion'] as $paso)<div class="mb-1">{{ $paso }}</div>@endforeach
@foreach ($estimacion['demoras_activas'] as $demora)<div class="mb-1"><strong>{{ $demora['alcance'] }}:</strong> {{ $demora['motivo'] }} (+{{ $demora['dias'] }} días hábiles)</div>@endforeach
</div></details>@endif
