/**
 * fechas.js — Fechas en español con Intl: «hace 2 días» y fecha completa.
 */

// Se crean una sola vez: construir un Intl.* en cada llamada es lento.
const relativo = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });
const completo = new Intl.DateTimeFormat('es-ES', { dateStyle: 'full', timeStyle: 'short' });

/**
 * Fecha completa en español, por ejemplo «lunes, 2 de noviembre de 2026, 18:30».
 * @param {Date} fecha
 * @returns {string}
 */
export function formatearFecha(fecha) {
  return completo.format(fecha);
}

/**
 * Tiempo transcurrido en lenguaje natural: «hace 2 días», «hace 3 horas»...
 * Usa la unidad más grande que quepa en la diferencia.
 * @param {Date} fecha Momento pasado.
 * @returns {string}
 */
export function haceCuanto(fecha) {
  // Negativo = pasado, que es lo que espera RelativeTimeFormat para «hace ...».
  const minutos = Math.round((fecha.getTime() - Date.now()) / 60000);

  if (Math.abs(minutos) >= 1440) return relativo.format(Math.trunc(minutos / 1440), 'day');
  if (Math.abs(minutos) >= 60) return relativo.format(Math.trunc(minutos / 60), 'hour');
  return relativo.format(minutos, 'minute');
}
