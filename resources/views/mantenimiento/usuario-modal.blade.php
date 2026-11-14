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
