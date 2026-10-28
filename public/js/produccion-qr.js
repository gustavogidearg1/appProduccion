/* QR de la ficha: se extraen datos y siempre se abre la aplicación actual. */
(function (root) {
    'use strict';
    function interpretarQR(texto, base) {
        let codigo;
        try { codigo = new URL(String(texto).trim(), base); }
        catch (_) { throw new Error('No se pudo interpretar el código QR.'); }
        if (!['http:', 'https:'].includes(codigo.protocol) || codigo.username || codigo.password) throw new Error('Este QR no corresponde a una ficha de producción.');
        const destino = new URL(base);
        const ruta = codigo.pathname.replace(/\/$/, '');
        const esMovil = ruta === destino.pathname.replace(/\/$/, '') || ruta.endsWith('/produccion/movil');
        const ficha = ruta.match(/\/planes\/([1-9]\d*)\/productos\/([1-9]\d*)\/unidades\/([1-9]\d*)\/?$/);
        const entero = valor => typeof valor === 'string' && /^[1-9]\d{0,17}$/.test(valor);
        destino.search = ''; destino.hash = '';
        if (ficha) { destino.searchParams.set('unidad', ficha[3]); return destino.href; }
        if (!esMovil) throw new Error('Este QR no corresponde a una ficha de producción.');
        const unidad = codigo.searchParams.get('unidad');
        if (entero(unidad)) { destino.searchParams.set('unidad', unidad); return destino.href; }
        const plan = codigo.searchParams.get('plan');
        const chasis = (codigo.searchParams.get('chasis') || '').trim();
        if (!entero(plan) || !chasis || chasis.length > 100) throw new Error('El QR no contiene una unidad o un plan y chasis válidos.');
        destino.searchParams.set('plan', plan); destino.searchParams.set('chasis', chasis);
        return destino.href;
    }
    if (typeof module !== 'undefined' && module.exports) { module.exports = { interpretarQR }; return; }
    root.ProduccionQR = { interpretarQR };
    document.addEventListener('DOMContentLoaded', () => {
        const panel = document.getElementById('lector-qr');
        if (!panel) return;
        const base = panel.dataset.buscarUrl;
        const video = document.getElementById('qr-video');
        const canvas = document.getElementById('qr-canvas');
        const contexto = canvas.getContext('2d', { willReadFrequently: true });
        const iniciar = document.getElementById('qr-iniciar');
        const mensaje = document.getElementById('qr-mensaje');
        let stream = null, activo = false, intento = 0, temporizador = null;
        function informar(texto, error = false) { mensaje.textContent = texto; mensaje.className = `small mt-2 ${error ? 'text-danger' : 'text-muted'}`; }
        function detener() {
            intento++; activo = false;
            if (temporizador !== null) clearTimeout(temporizador);
            stream?.getTracks().forEach(pista => pista.stop()); stream = null;
            video.pause(); video.srcObject = null;
            document.getElementById('qr-video-panel').hidden = true; iniciar.disabled = false;
        }
        function abrir(texto) {
            try { const url = interpretarQR(texto, base); detener(); informar('QR reconocido. Cargando unidad…'); root.location.assign(url); return true; }
            catch (error) { informar(error.message, true); return false; }
        }
        function leerImagen(origen, ancho, alto, maximo, invertir) {
            if (typeof root.jsQR !== 'function') throw new Error('No se cargó el lector QR. Actualizá la página o usá la cámara del celular para abrir el enlace.');
            const escala = Math.min(1, maximo / Math.max(ancho, alto));
            canvas.width = Math.max(1, Math.round(ancho * escala)); canvas.height = Math.max(1, Math.round(alto * escala));
            contexto.drawImage(origen, 0, 0, canvas.width, canvas.height);
            const datos = contexto.getImageData(0, 0, canvas.width, canvas.height);
            return root.jsQR(datos.data, datos.width, datos.height, { inversionAttempts: invertir ? 'attemptBoth' : 'dontInvert' });
        }
        function escanear() {
            if (!activo) return;
            try {
                if (video.readyState >= 2 && video.videoWidth) {
                    const qr = leerImagen(video, video.videoWidth, video.videoHeight, 900, false);
                    if (qr && abrir(qr.data)) return;
                }
            } catch (error) { detener(); informar(error.message, true); return; }
            temporizador = setTimeout(escanear, 200);
        }
        iniciar.addEventListener('click', async () => {
            detener();
            if (!root.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                informar('Para abrir la cámara dentro del navegador necesitás HTTPS. Podés leer una foto del QR o usar la cámara del celular para abrir el enlace.', true); return;
            }
            const actual = ++intento;
            activo = true; iniciar.disabled = true; document.getElementById('qr-video-panel').hidden = false;
            informar('Esperando permiso de cámara…');
            try {
                const captura = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }, audio: false });
                if (actual !== intento) { captura.getTracks().forEach(pista => pista.stop()); return; }
                stream = captura; video.srcObject = stream; await video.play();
                if (actual !== intento) return;
                informar('Apuntá la cámara al QR de la ficha del chasis.'); escanear();
            } catch (error) {
                if (actual !== intento) return;
                detener();
                const texto = error.name === 'NotAllowedError' ? 'No se habilitó la cámara. Podés permitirla en el navegador o leer una foto del QR.' : 'No se pudo abrir la cámara. Probá con una foto del código QR.';
                informar(texto, true);
            }
        });
        document.getElementById('qr-detener').addEventListener('click', () => {detener(); informar('Cámara cerrada.');});
        document.getElementById('qr-abrir').addEventListener('click', () => abrir(document.getElementById('qr-texto').value));
        document.getElementById('qr-texto').addEventListener('keydown', evento => { if (evento.key === 'Enter') { evento.preventDefault(); abrir(evento.target.value); } });
        document.getElementById('qr-imagen').addEventListener('change', async evento => {
            detener();
            const actual = intento;
            const archivo = evento.target.files?.[0]; if (!archivo) return;
            if (!archivo.type.startsWith('image/') || archivo.size > 15 * 1024 * 1024) { informar('Elegí una imagen del QR de hasta 15 MB.', true); evento.target.value = ''; return; }
            const url = URL.createObjectURL(archivo);
            informar('Leyendo QR de la imagen…');
            try {
                const imagen = new Image();
                await new Promise((resolve, reject) => { imagen.onload = resolve; imagen.onerror = reject; imagen.src = url; });
                if (actual !== intento) return;
                const qr = leerImagen(imagen, imagen.naturalWidth, imagen.naturalHeight, 1800, true);
                if (qr) abrir(qr.data); else informar('No se encontró un QR legible. Probá una foto más cercana y nítida.', true);
            } catch (_) { informar('No se pudo leer esta imagen. Probá con JPG o PNG.', true); }
            finally { URL.revokeObjectURL(url); evento.target.value = ''; }
        });
        root.addEventListener('pagehide', detener);
        document.addEventListener('visibilitychange', () => { if (document.hidden) detener(); });
    });
})(typeof window !== 'undefined' ? window : globalThis);
