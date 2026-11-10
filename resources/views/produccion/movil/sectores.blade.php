<div class="card shadow-sm">
    <div class="card-header">
        <h2 class="h4 mb-0">Avance global · Plan #{{ $plan->numero }}</h2>
    </div>
    <div class="card-body">
        @include('planificacion.mensajes')
        <p class="text-muted">Iniciar y Finalizar registran la fecha de hoy para todo el plan en el sector elegido.</p>
        @if ($plan->estado !== 'abierto')
            <p class="alert alert-secondary">Plan {{ $plan->estado }}. Los avances se muestran para consulta.</p>
        @endif

        <div class="list-group">
            @forelse ($sectores as $sector)
                @php
                    $etapa = $sector['etapa'];
                    $avance = $sector['avance'];
                    $estado = $avance?->estado ?? 'pendiente';
                @endphp
                <div class="list-group-item p-3">
                    <h3 class="h6 mb-1">{{ $etapa->nombre }}</h3>
                    <div class="small mb-2">{{ ['pendiente'=>'Pendiente','en_proceso'=>'En proceso','completada'=>'Completada'][$estado] ?? $estado }}</div>
                    <div class="small text-muted mb-2">
                        Inicio real {{ $avance?->fecha_inicio?->format('d/m/Y') ?? '—' }} · Fin real {{ $avance?->fecha_fin?->format('d/m/Y') ?? '—' }}
                    </div>
                    @if ($plan->estado === 'abierto' && !auth()->user()->puedeOperarEtapaGlobal($etapa))
                        <div class="small text-muted">Solo consulta.</div>
                    @elseif ($plan->estado === 'abierto' && ($estado === 'en_proceso' || ($estado === 'pendiente' && $sector['habilitada'])))
                        <form method="POST" action="{{ route($estado === 'en_proceso' ? 'planes.avances.finalizar' : 'planes.avances.iniciar', [$plan, $etapa]) }}">
                            @csrf
                            <button type="submit" class="btn {{ $estado === 'en_proceso' ? 'btn-success' : 'btn-primary' }} w-100">
                                {{ $estado === 'en_proceso' ? 'Finalizar' : 'Iniciar' }} {{ $etapa->nombre }}
                            </button>
                        </form>
                    @elseif ($plan->estado === 'abierto' && $estado === 'pendiente')
                        <div class="small text-muted">Pendiente de etapas previas.</div>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">No hay sectores activos.</p>
            @endforelse
        </div>
    </div>
</div>
