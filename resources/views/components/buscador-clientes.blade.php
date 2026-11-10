@props(['clientes', 'seleccion', 'texto' => null])

@php($clienteSeleccionado = $clientes->firstWhere('id', $seleccion))

<div data-selector-cliente>
    <label class="form-label" for="cliente-elegido">Cliente</label>
    <input type="hidden" name="cliente_id" value="{{ $clienteSeleccionado?->id }}">
    <div class="input-group">
        <input id="cliente-elegido" class="form-control" @if ($texto === null) readonly @else type="search" name="cliente" maxlength="255" @endif
               value="{{ $clienteSeleccionado?->nombre ?? $texto }}"
               placeholder="Seleccionar cliente">
        <button type="button" class="btn btn-outline-primary" data-abrir aria-haspopup="dialog"
                aria-label="Buscar cliente" title="Buscar cliente">
            @if ($texto === null)
                Buscar cliente
            @else
                <i class="bi bi-search" aria-hidden="true"></i>
            @endif
        </button>
    </div>

    <dialog class="border-0 rounded shadow p-0" style="width:min(760px, 95vw)" aria-labelledby="buscar-cliente-titulo">
        <div class="bg-primary text-white d-flex justify-content-between align-items-center p-3">
            <h2 class="h5 mb-0" id="buscar-cliente-titulo">Buscar cliente</h2>
            <button type="button" class="btn-close btn-close-white" data-cerrar aria-label="Cerrar"></button>
        </div>
        <div class="p-3">
            <label for="buscar-cliente-texto" class="form-label">Razón social, código del sistema o CUIT</label>
            <input type="search" id="buscar-cliente-texto" class="form-control mb-2" autocomplete="off">
            <p class="small text-muted mb-2" role="status"></p>
            <div class="overflow-auto" style="max-height:50vh">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top table-light"><tr><th>Código</th><th>Cliente</th><th>CUIT</th><th>Acción</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </dialog>
</div>
<style>[data-selector-cliente] dialog::backdrop { background: #0008; }</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selector = document.querySelector('[data-selector-cliente]');
    const modal = selector.querySelector('dialog');
    const campo = selector.querySelector('#cliente-elegido');
    campo.addEventListener('input', () => { selector.querySelector('[name="cliente_id"]').value = ''; });
    const abrir = selector.querySelector('[data-abrir]');
    const texto = modal.querySelector('input[type="search"]');
    const resultados = modal.querySelector('tbody');
    const normalizar = valor => String(valor ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const clientes = {{ Js::from($clientes->map->only(['id','nombre','CodSistema','cuit'])->values()) }}.map(cliente => ({
        ...cliente, indice: normalizar([cliente.nombre, cliente.CodSistema, cliente.cuit].join(' '))
    }));

    function buscar() {
        const palabras = normalizar(texto.value).trim().split(/\s+/).filter(Boolean);
        const coincidencias = clientes.filter(cliente => palabras.every(palabra => cliente.indice.includes(palabra)));
        resultados.replaceChildren();
        for (const cliente of coincidencias.slice(0, 50)) {
            const fila = resultados.insertRow();
            for (const dato of [cliente.CodSistema, cliente.nombre, cliente.cuit]) fila.insertCell().textContent = dato || '—';
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'btn btn-sm btn-success';
            boton.textContent = 'Seleccionar';
            boton.addEventListener('click', () => {
                selector.querySelector('[name="cliente_id"]').value = cliente.id;
                campo.value = cliente.nombre;
                modal.close();
            });
            fila.insertCell().append(boton);
        }
        modal.querySelector('[role="status"]').textContent = coincidencias.length > 50
            ? 'Se muestran los primeros 50 resultados. Escribí más para acotar.'
            : coincidencias.length ? String(coincidencias.length) + ' coincidencias' : 'No hay clientes que coincidan.';
    }

    abrir.addEventListener('click', () => {
        texto.value = '';
        buscar();
        modal.showModal();
        texto.focus();
    });
    texto.addEventListener('input', buscar);
    texto.addEventListener('keydown', evento => { if (evento.key === 'Enter') evento.preventDefault(); });
    modal.querySelector('[data-cerrar]').addEventListener('click', () => modal.close());
    modal.addEventListener('close', () => abrir.focus());
});
</script>
