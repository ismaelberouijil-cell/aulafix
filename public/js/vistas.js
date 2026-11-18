/**
 * vistas.js — Lógica del panel (resumir, filtrar, ordenar) y pintado de la tabla.
 * Los datos de usuarios se insertan SIEMPRE con textContent: innerHTML interpretaría
 * etiquetas y permitiría meter código en la página (XSS).
 */
import { haceCuanto, formatearFecha } from './fechas.js';

const PESO_PRIORIDAD = { baja: 1, media: 2, alta: 3 };
const ETIQUETAS_ESTADO = { abierta: 'Abierta', en_curso: 'En curso', resuelta: 'Resuelta' };

/**
 * Cuenta las incidencias de cada estado y las urgentes, recorriendo la lista una vez con reduce.
 * @param {import('./incidencia.js').Incidencia[]} lista
 * @returns {{abierta:number, en_curso:number, resuelta:number, urgentes:number}}
 */
export function resumir(lista) {
  return lista.reduce((resumen, incidencia) => {
    resumen[incidencia.estado] += 1;
    if (incidencia.esUrgente) resumen.urgentes += 1;
    return resumen;
  }, { abierta: 0, en_curso: 0, resuelta: 0, urgentes: 0 });
}

/**
 * Filtra por pestaña, prioridad y texto (equipo, descripción o autor).
 * filter() devuelve un array nuevo: la lista original no se modifica.
 * @param {import('./incidencia.js').Incidencia[]} lista
 * @param {{pestana:string, texto:string, prioridad:string}} filtros
 * @returns {import('./incidencia.js').Incidencia[]}
 */
export function filtrar(lista, { pestana, texto, prioridad }) {
  const busqueda = texto.trim().toLowerCase();
  return lista.filter((i) => {
    const coincidePestana = pestana === 'todas' || i.estado === pestana;
    const coincidePrioridad = prioridad === 'todas' || i.prioridad === prioridad;
    const coincideTexto = [i.equipo, i.descripcion, i.autor].some((t) => t.toLowerCase().includes(busqueda));
    return coincidePestana && coincidePrioridad && coincideTexto;
  });
}

/**
 * Ordena SIN modificar el array original. sort() cambia el array sobre el que se
 * llama, y el original debe quedar intacto para poder volver a filtrar desde él;
 * por eso se ordena una copia hecha con [...lista].
 * @param {import('./incidencia.js').Incidencia[]} lista
 * @param {string} campo 'fecha', 'equipo' o 'prioridad'.
 * @param {string} dir 'asc' o 'desc'.
 * @returns {import('./incidencia.js').Incidencia[]} Copia ordenada.
 */
export function ordenar(lista, campo, dir) {
  const comparadores = {
    fecha: (a, b) => a.creada.getTime() - b.creada.getTime(),
    equipo: (a, b) => a.equipo.localeCompare(b.equipo, 'es', { numeric: true }),
    prioridad: (a, b) => PESO_PRIORIDAD[a.prioridad] - PESO_PRIORIDAD[b.prioridad],
  };
  const comparar = comparadores[campo] ?? comparadores.fecha;
  const signo = dir === 'asc' ? 1 : -1;
  return [...lista].sort((a, b) => signo * comparar(a, b));
}

/**
 * Crea un elemento con texto y clase opcionales. El texto va con textContent.
 * @param {string} etiqueta Nombre de la etiqueta HTML.
 * @param {string} [texto]
 * @param {string} [clase]
 * @returns {HTMLElement}
 */
function crear(etiqueta, texto = '', clase = '') {
  const elemento = document.createElement(etiqueta);
  elemento.textContent = texto;
  if (clase !== '') elemento.className = clase;
  return elemento;
}

/**
 * Repinta las filas de la tabla con las incidencias recibidas.
 * @param {HTMLElement} cuerpo El <tbody> que se rellena.
 * @param {import('./incidencia.js').Incidencia[]} lista Incidencias ya filtradas y ordenadas.
 */
export function pintarTabla(cuerpo, lista) {
  cuerpo.replaceChildren(); // vacía la tabla antes de pintar

  if (lista.length === 0) {
    const fila = crear('tr');
    const celda = crear('td', 'No hay incidencias que coincidan con los filtros.');
    celda.colSpan = 7;
    fila.append(celda);
    cuerpo.append(fila);
    return;
  }

  for (const i of lista) {
    // Las urgentes llevan una clase CSS (no estilos en línea) para resaltarlas.
    const fila = crear('tr', '', i.esUrgente ? 'urgente' : '');

    // «hace 2 días» en pantalla y la fecha completa al pasar el ratón (atributo title).
    const tiempo = crear('time', haceCuanto(i.creada));
    tiempo.title = formatearFecha(i.creada);
    const celdaFecha = crear('td');
    celdaFecha.append(tiempo);

    // El equipo es un enlace al detalle de la incidencia.
    const enlace = crear('a', i.equipo);
    enlace.href = `incidencia.php?id=${i.id}`;
    const celdaEquipo = crear('td');
    celdaEquipo.append(enlace);

    fila.append(
      celdaFecha,
      celdaEquipo,
      crear('td', i.categoria),
      crear('td', i.prioridad, `prio-${i.prioridad}`),
      crear('td', ETIQUETAS_ESTADO[i.estado], `estado-${i.estado}`),
      crear('td', i.descripcion),
      crear('td', i.autor),
    );
    cuerpo.append(fila);
  }
}
