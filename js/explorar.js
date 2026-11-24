/* explorar.js · P11
 * El ciclo de siempre:
 *   1 · pedir      → fetch
 *   2 · revisar    → res.ok / res.status
 *   3 · convertir  → res.json()
 *   4 · pintar     → DOM
 */

// Los huecos de la página (los id de explorar.php)
const lista    = document.getElementById('lista');
const estado   = document.getElementById('estado');
const campoQ   = document.getElementById('q');
const campoCat = document.getElementById('categoria');

// Mismo criterio de color que index.php: teal para Trabajo y Estudio, el resto azul
const CATEGORIAS_TEAL = ['Trabajo', 'Estudio'];

// '2026-11-20' → '20/11/2026'
function formatearFecha(fecha) {
    const [a, m, d] = fecha.split('-');
    return `${d}/${m}/${a}`;
}

// Crea UNA tarjeta a partir de UN evento del JSON.
// Todo con textContent: pone texto, nunca HTML (es el e() de JavaScript).
function crearTarjeta(ev) {
    const tarjeta = document.createElement('article');
    tarjeta.className = 'card';

    const etiqueta = document.createElement('span');
    etiqueta.className = 'card__badge '
        + (CATEGORIAS_TEAL.includes(ev.categoria) ? 'badge-teal' : 'badge-azul');
    etiqueta.textContent = ev.categoria;

    const titulo = document.createElement('h2');
    titulo.className = 'card__title';
    titulo.textContent = ev.titulo;

    const cuando = document.createElement('p');
    cuando.className = 'card__meta';
    cuando.textContent = formatearFecha(ev.fecha) + (ev.hora ? ' · ' + ev.hora : '');

    tarjeta.append(etiqueta, titulo, cuando);

    if (ev.descripcion) {                  // null o '' → no se pinta
        const texto = document.createElement('p');
        texto.className = 'card__text';
        texto.textContent = ev.descripcion;
        tarjeta.append(texto);
    }
    return tarjeta;
}

// Pinta la lista completa
function pintar(datos) {
    lista.replaceChildren();               // borra lo que había antes
    const buscando = datos.filtros.q !== '' || datos.filtros.categoria !== 0;

    if (datos.total === 0) {
        estado.textContent = buscando ? 'Sin resultados para tu búsqueda.'
                                      : 'Aún no tienes eventos registrados.';
        return;
    }
    const s = datos.total === 1 ? '' : 's';             // singular o plural
    estado.textContent = `${datos.total} evento${s} `
                       + (buscando ? `encontrado${s}` : `registrado${s}`);
    datos.eventos.forEach(ev => lista.append(crearTarjeta(ev)));
}

async function cargarEventos() {
    const params = new URLSearchParams({ q: campoQ.value.trim(), categoria: campoCat.value });
    estado.textContent = 'Cargando eventos…';

    try {
        const res = await fetch('api.php?' + params);    // 1 · pedir

        if (res.status === 401) {                        // 2 · revisar
            estado.innerHTML = 'Tu sesión terminó. <a href="login.php">Inicia sesión</a> de nuevo.';
            lista.replaceChildren();
            return;
        }
        if (!res.ok) {
            throw new Error('La API respondió ' + res.status);
        }

        const datos = await res.json();                  // 3 · convertir
        pintar(datos);                                   // 4 · pintar
    } catch (error) {
        // Sin red, servidor caído o JSON roto
        console.error(error);
        estado.textContent = 'No se pudieron cargar los eventos. Intenta de nuevo más tarde.';
        lista.replaceChildren();
    }
}

// Espera 300 ms a que dejes de teclear antes de pedir (debounce)
let espera;
campoQ.addEventListener('input', () => {
    clearTimeout(espera);
    espera = setTimeout(cargarEventos, 300);
});
campoCat.addEventListener('change', cargarEventos);

// Si presionan Enter, que no se recargue la página
document.getElementById('filtros').addEventListener('submit', e => e.preventDefault());

cargarEventos();                                         // primera carga