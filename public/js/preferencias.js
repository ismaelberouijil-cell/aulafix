/**
 * preferencias.js — localStorage: recuerda la pestaña, los filtros y el orden del panel.
 * Solo se guardan preferencias de pantalla, nada sensible: localStorage lo puede
 * leer cualquier script de la página.
 */
const CLAVE = 'aulafix.preferencias';

// Valores con los que empieza el panel la primera vez.
const INICIALES = { pestana: 'todas', texto: '', prioridad: 'todas', campo: 'fecha', dir: 'desc' };

/**
 * Lee las preferencias guardadas. try/catch porque localStorage puede estar
 * bloqueado (modo privado) o contener un JSON roto.
 * @returns {{pestana:string, texto:string, prioridad:string, campo:string, dir:string}}
 */
export function leerPreferencias() {
  try {
    return { ...INICIALES, ...JSON.parse(localStorage.getItem(CLAVE)) };
  } catch {
    return { ...INICIALES };
  }
}

/**
 * Guarda las preferencias. Si falla, la aplicación sigue funcionando sin recordarlas.
 * @param {object} preferencias Estado del panel.
 */
export function guardarPreferencias(preferencias) {
  try {
    localStorage.setItem(CLAVE, JSON.stringify(preferencias));
  } catch {
    // Sin almacenamiento: no pasa nada.
  }
}
