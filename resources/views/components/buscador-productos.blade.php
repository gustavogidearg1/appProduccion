@php
    $familiasBuscador = $productos->pluck('familia')->filter()->unique('id')->sortBy('nombre');
    $tiposBuscador = $productos->pluck('tipo')->filter()->unique('id')->sortBy('nombre');
@endphp
<style>
dialog.buscador-productos{width:min(1100px,95vw);max-height:90vh;padding:0;border:0;border-radius:10px;box-shadow:0 12px 45px #0005}dialog.buscador-productos::backdrop{background:#0008}.buscador-productos .buscador-tabla{max-height:50vh;overflow:auto}.buscador-productos th{position:sticky;top:0;z-index:1;background:#f8f9fa}.buscador-productos [hidden]{display:none!important}body:has(dialog.buscador-productos[open]){overflow:hidden}
</style>
<dialog id="buscador-productos" class="buscador-productos" aria-labelledby="buscador-productos-titulo">
<div class="bg-primary text-white d-flex justify-content-between align-items-center p-3"><h2 class="h5 mb-0" id="buscador-productos-titulo">Buscar producto</h2><button type="button" class="btn-close btn-close-white" data-cerrar-buscador aria-label="Cerrar buscador"></button></div>
<div class="p-3">
<div class="row g-3 mb-3">
<div class="col-md-6"><label for="buscador-texto" class="form-label">Código, nombre o familia</label><input type="search" id="buscador-texto" class="form-control" placeholder="Ej.: TA-30, balanza, 2200" autocomplete="off"></div>
<div class="col-md-3"><label for="buscador-familia" class="form-label">Familia</label><select id="buscador-familia" class="form-select"><option value="">Todas</option>@foreach ($familiasBuscador as $familia)<option value="{{ $familia->id }}">{{ $familia->nombre }}</option>@endforeach</select></div>
<div class="col-md-3"><label for="buscador-tipo" class="form-label">Tipo</label><select id="buscador-tipo" class="form-select"><option value="">Todos</option>@foreach ($tiposBuscador as $tipo)<option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>@endforeach</select></div>
</div>
<div class="d-flex justify-content-between gap-2 mb-2"><span id="buscador-conteo" class="small text-muted" role="status" aria-live="polite"></span><button type="button" class="btn btn-sm btn-outline-secondary" id="buscador-limpiar">Limpiar filtros</button></div>
<div class="buscador-tabla"><table class="table table-bordered table-hover align-middle mb-0"><thead><tr><th>Código</th><th>Nombre</th><th>Familia</th><th>Tipo</th><th>UE</th><th>Acción</th></tr></thead><tbody>
@foreach ($productos as $producto)
<tr data-producto-buscador data-id="{{ $producto->id }}" data-codigo="{{ $producto->codigo }}" data-nombre="{{ $producto->nombre }}" data-familia="{{ $producto->familia_id }}" data-familia-nombre="{{ $producto->familia?->nombre }}" data-tipo="{{ $producto->tipo_id }}" data-tipo-nombre="{{ $producto->tipo?->nombre }}" data-ue="{{ $producto->ue_produccion }}">
<td>{{ $producto->codigo ?? '-' }}</td><td>{{ $producto->nombre }}</td><td>{{ $producto->familia?->nombre ?? '-' }}</td><td>{{ $producto->tipo?->nombre ?? '-' }}</td><td>{{ $producto->ue_produccion ?? '-' }}</td><td><button type="button" class="btn btn-sm btn-success" data-seleccionar-producto>Seleccionar</button></td>
</tr>@endforeach
</tbody></table></div>
<div id="buscador-sin-resultados" class="text-center text-muted p-4" hidden>No hay productos que coincidan. Probá con otro código o limpiá los filtros.</div>
<p class="small text-muted mt-2 mb-0">Se muestran productos activos del Cotizador. Seleccioná un producto para completar la fila desde la que abriste el buscador.</p>
</div>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('buscador-productos');
    const texto = modal.querySelector('#buscador-texto');
    const familia = modal.querySelector('#buscador-familia');
    const tipo = modal.querySelector('#buscador-tipo');
    const normalizar = valor => String(valor ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const filas = [...modal.querySelectorAll('[data-producto-buscador]')].map(elemento => ({
        elemento,
        indice: normalizar([elemento.dataset.codigo, elemento.dataset.nombre, elemento.dataset.familiaNombre, elemento.dataset.tipoNombre].join(' ')),
        boton: elemento.querySelector('[data-seleccionar-producto]')
    }));
    let destino = null;
    let excluidos = new Set();
    let disparador = null;
    function filtrar() {
        const palabras = normalizar(texto.value).split(/\s+/).filter(Boolean);
        let visibles = 0;
        for (const fila of filas) {
            const datos = fila.elemento.dataset;
            const visible = palabras.every(palabra => fila.indice.includes(palabra)) && (!familia.value || familia.value === datos.familia) && (!tipo.value || tipo.value === datos.tipo);
            fila.elemento.hidden = !visible;
            fila.boton.disabled = excluidos.has(datos.id);
            fila.boton.textContent = fila.boton.disabled ? 'Ya agregado' : 'Seleccionar';
            if (visible) visibles++;
        }
        modal.querySelector('#buscador-conteo').textContent = `${visibles} productos encontrados`;
        modal.querySelector('#buscador-sin-resultados').hidden = visibles > 0;
    }
    window.buscadorProductos = {
        abrir(elemento, idsExcluidos = []) {
            destino = elemento;
            disparador = document.activeElement;
            excluidos = new Set(idsExcluidos.map(String));
            filtrar();
            modal.showModal();
            texto.focus();
        }
    };
    texto.addEventListener('input', filtrar);
    familia.addEventListener('change', filtrar);
    tipo.addEventListener('change', filtrar);
    modal.querySelector('#buscador-limpiar').addEventListener('click', () => {
        texto.value = ''; familia.value = ''; tipo.value = ''; filtrar(); texto.focus();
    });
    modal.querySelector('[data-cerrar-buscador]').addEventListener('click', () => modal.close());
    modal.addEventListener('click', evento => {
        const boton = evento.target.closest('[data-seleccionar-producto]');
        if (!boton || boton.disabled || !destino?.isConnected) return;
        const datos = boton.closest('[data-producto-buscador]').dataset;
        destino.dispatchEvent(new CustomEvent('producto-seleccionado', {bubbles:true, detail:{id:datos.id, codigo:datos.codigo, nombre:datos.nombre, ue:datos.ue}}));
        modal.close();
    });
    modal.addEventListener('close', () => {if (disparador?.isConnected) disparador.focus(); destino = null;});
});
</script>
