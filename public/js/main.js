/**
 * main.js — Punto de entrada del panel de incidencias: lee el JSON que incrusta
 * el servidor, copia la plantilla del panel y conecta pestañas, filtros y ordenación.
 */
import { Incidencia } from './incidencia.js';
import { resumir, filtrar, ordenar, pintarTabla } from './vistas.js';
import { leerPreferencias, guardarPreferencias } from './preferencias.js';

const contenedor = document.getElementById('panel-contenedor');
const origen = document.getElementById('datos-incidencias');
const plantilla = document.getElementById('plantilla-panel');

// Si falta algo, no se hace nada y se queda la tabla de PHP.
if (contenedor && origen && plantilla) {
  iniciar();
}

/** Monta el panel con los datos de la página y lo conecta con los eventos. */
function iniciar() {
  const incidencias = JSON.parse(origen.textContent).map((datos) => new Incidencia(datos));

  // Estado del panel: lo último que eligió el usuario (o los valores iniciales).
  const estado = leerPreferencias();

  // Sustituye la tabla de PHP por una copia de la plantilla.
  contenedor.replaceChildren(plantilla.content.cloneNode(true));
  const buscador = document.getElementById('buscador');
  const prioridad = document.getElementById('prioridad');
  buscador.value = estado.texto;
  prioridad.value = estado.prioridad;

  /**
   * Recalcula lo que se ve a partir del estado y lo repinta. Siempre se parte del
   * array original, que nunca se modifica: filtrar y ordenar devuelven copias.
   */
  function actualizar() {
    const visibles = ordenar(filtrar(incidencias, estado), estado.campo, estado.dir);

    // Tarjetas del resumen.
    const resumen = resumir(incidencias);
    for (const clave of ['abierta', 'en_curso', 'resuelta', 'urgentes']) {
      document.getElementById(`n-${clave}`).textContent = String(resumen[clave]);
    }

    // La pestaña activa se marca con aria-selected.
    for (const boton of document.querySelectorAll('.pestana')) {
      boton.setAttribute('aria-selected', String(boton.dataset.estado === estado.pestana));
    }

    pintarTabla(document.getElementById('cuerpo-tabla'), visibles);
    document.getElementById('contador').textContent =
      `Mostrando ${visibles.length} de ${incidencias.length} incidencias`;
    guardarPreferencias(estado);
  }

  for (const boton of document.querySelectorAll('.pestana')) {
    boton.addEventListener('click', () => {
      estado.pestana = boton.dataset.estado;
      actualizar();
    });
  }

  // 'input' se dispara con cada tecla: la tabla se filtra mientras se escribe.
  buscador.addEventListener('input', () => {
    estado.texto = buscador.value;
    actualizar();
  });

  prioridad.addEventListener('change', () => {
    estado.prioridad = prioridad.value;
    actualizar();
  });

  for (const boton of document.querySelectorAll('.orden')) {
    boton.addEventListener('click', () => {
      const campo = boton.dataset.campo;
      // Otro clic en la misma cabecera invierte el orden; en otra, empieza ascendente.
      estado.dir = estado.campo === campo && estado.dir === 'asc' ? 'desc' : 'asc';
      estado.campo = campo;
      actualizar();
    });
  }

  actualizar();
}
