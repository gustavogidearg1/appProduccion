@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Controles de calidad</h1>
                <p class="text-muted mb-0">
                    Familia: {{ $familia->nombre }}
                </p>
            </div>

            <a href="{{ \Illuminate\Support\Facades\DB::table('calidad_documentos')->where('familia_id',$familia->id)->exists() ? route('calidad.documentos.index',$familia) : route('calidad.familias.controles.create', $familia) }}" class="btn btn-primary">
                Nuevo control
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card card-body mb-3"><h2 class="h5">Planilla documental de la familia</h2><p>Administrá código, revisiones, imágenes e impresión. Los cambios se preparan en borrador.</p><a href="{{ route('calidad.documentos.index',$familia) }}" class="btn btn-outline-primary">Planilla y revisiones</a></div>
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="h5 mb-0">Controles asignados</h2>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Orden</th>
                                <th>Control</th>
                                <th>Etapa</th>
                                <th>Tipo de respuesta</th>
                                <th>Respuesta esperada</th>
                                <th>Obligatorio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($controlesFamilia as $item)
                                <tr>
                                    <td>{{ $item->orden }}</td>

                                    <td>
                                        <strong>
                                            {{ $item->control->nombre }}
                                        </strong>

                                        @if ($item->control->descripcion)
                                            <br>
                                            <small class="text-muted">
                                                {{ $item->control->descripcion }}
                                            </small>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $item->etapa?->nombre ?? '-' }}
                                    </td>

                                    <td>
                                        @switch($item->control->tipo_respuesta)
                                            @case('si_no')
                                                Sí / No
                                            @break

                                            @case('valor')
                                                Valor
                                            @break

                                            @case('texto')
                                                Texto
                                            @break

                                            @default
                                                {{ $item->control->tipo_respuesta }}
                                        @endswitch
                                    </td>

                                    <td>
                                        {{ $item->respuesta_esperada ?: '-' }}

                                        @if ($item->unidad_medida)
                                            <br>
                                            <small class="text-muted">
                                                Unidad: {{ $item->unidad_medida }}
                                            </small>
                                        @endif

                                        @if ($item->valor_minimo !== null || $item->valor_maximo !== null)
                                            <br>
                                            <small class="text-muted">
                                                Rango:
                                                {{ $item->valor_minimo ?? '-' }}
                                                a
                                                {{ $item->valor_maximo ?? '-' }}
                                            </small>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($item->obligatorio)
                                            <span class="badge bg-danger">
                                                Sí
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                No
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($item->activo)
                                            <span class="badge bg-success">
                                                Activo
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (\Illuminate\Support\Facades\DB::table('calidad_documentos')->where('familia_id',$familia->id)->exists())
<form method="POST" action="{{ route('calidad.documentos.editarItem',[$familia,$item->control_calidad_id]) }}">@csrf<button class="btn btn-sm btn-warning">Editar</button></form>
@else<a href="{{ route('calidad.familias.controles.edit',[$familia,$item]) }}" class="btn btn-sm btn-warning">Editar</a>@endif

                                        @if (!\Illuminate\Support\Facades\DB::table('calidad_documentos')->where('familia_id',$familia->id)->exists())<form method="POST" class="d-inline"
                                            action="{{ route('calidad.familias.controles.destroy', [$familia, $item]) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Quitar este control?')">
                                                Quitar
                                            </button>
                                        </form>@endif
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No hay controles asignados a esta familia.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    @endsection
