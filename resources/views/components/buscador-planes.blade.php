@props(['id', 'name', 'planes', 'seleccion' => null])

@php($elegido = collect($planes)->first(fn ($plan) => (string) $plan['id'] === (string) $seleccion))

<div data-buscador-planes>
    <label class="form-label" for="{{ $id }}">Plan</label>
    <input type="hidden" name="{{ $name }}" value="{{ $elegido['id'] ?? '' }}">
    <div class="input-group">
        <input type="text" class="form-control" id="{{ $id }}" readonly
               value="{{ $elegido ? 'Plan #'.$elegido['numero'] : 'Todos los planes' }}">
        <button type="button" class="btn btn-outline-primary" data-abrir aria-haspopup="dialog"
                aria-controls="{{ $id }}-modal">
            <i class="bi bi-search" aria-hidden="true"></i> Buscar plan
        </button>
    </div>

    <dialog id="{{ $id }}-modal" class="border-0 rounded shadow p-0" style="width:min(760px, 95vw)"
            aria-labelledby="{{ $id }}-titulo">
        <div class="bg-primary text-white d-flex justify-content-between align-items-center p-3">
            <h2 class="h5 mb-0" id="{{ $id }}-titulo">Buscar plan</h2>
            <button type="button" class="btn-close btn-close-white" data-cerrar aria-label="Cerrar buscador"></button>
        </div>
        <div class="p-3">
            <label class="form-label" for="{{ $id }}-texto">Número de plan o producto</label>
            <input type="search" class="form-control mb-3" id="{{ $id }}-texto" autocomplete="off"
                   placeholder="Ej. 979 o MH-14">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <span class="small text-muted" role="status"></span>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-elegir=""
                        data-etiqueta="Todos los planes">Todos los planes</button>
            </div>
            <div class="overflow-auto" style="max-height:50vh">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="sticky-top table-light">
                        <tr><th>Plan</th><th>Productos</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($planes as $plan)
                            <tr data-plan>
                                <th scope="row">#{{ $plan['numero'] }}</th>
                                <td>{{ $plan['productos'] ?: '—' }}</td>
                                <td><button type="button" class="btn btn-sm btn-success"
                                            data-elegir="{{ $plan['id'] }}" data-etiqueta="Plan #{{ $plan['numero'] }}">Seleccionar</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </dialog>
</div>

@once
    <style>
        [data-buscador-planes] dialog::backdrop { background: #0008; }
        [data-buscador-planes] tr[hidden] { display: none !important; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

            for (const buscador of document.querySelectorAll('[data-buscador-planes]')) {
                const modal = buscador.querySelector('dialog');
                const abrir = buscador.querySelector('[data-abrir]');
                const texto = modal.querySelector('input[type="search"]');
                const estado = modal.querySelector('[role="status"]');
                const filas = [...modal.querySelectorAll('[data-plan]')].map(fila => ({
                    fila,
                    indice: normalizar([...fila.cells].slice(0, 2).map(celda => celda.textContent).join(' '))
                }));

                function filtrar() {
                    const palabras = normalizar(texto.value).trim().split(/\s+/).filter(Boolean);
                    let visibles = 0;
                    for (const {fila, indice} of filas) {
                        fila.hidden = !palabras.every(palabra => indice.includes(palabra));
                        if (!fila.hidden) visibles++;
                    }
                    estado.textContent = visibles ? (visibles === 1 ? '1 plan encontrado' : `${visibles} planes encontrados`) : 'No hay planes que coincidan';
                }

                abrir.addEventListener('click', () => {
                    texto.value = '';
                    filtrar();
                    modal.showModal();
                    texto.focus();
                });
                texto.addEventListener('input', filtrar);
                texto.addEventListener('keydown', evento => {
                    if (evento.key === 'Enter') evento.preventDefault();
                });
                modal.querySelector('[data-cerrar]').addEventListener('click', () => modal.close());
                modal.addEventListener('close', () => abrir.focus());
                modal.addEventListener('click', evento => {
                    const boton = evento.target.closest('[data-elegir]');
                    if (!boton) return;
                    buscador.querySelector('input[type="hidden"]').value = boton.dataset.elegir;
                    buscador.querySelector('input[readonly]').value = boton.dataset.etiqueta;
                    modal.close();
                });
            }
        });
    </script>
@endonce
