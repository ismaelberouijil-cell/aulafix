/**
 * incidencia.js — Clase Incidencia: convierte un objeto del JSON en un objeto
 * con una propiedad calculada (esUrgente).
 */
export class Incidencia {
  /**
   * @param {{id:number, equipo:string, categoria:string, prioridad:string,
   *          descripcion:string, estado:string, autor:string, creada_en:string}} datos
   *        Un elemento del JSON que manda el servidor.
   */
  constructor(datos) {
    this.id = datos.id;
    this.equipo = datos.equipo;
    this.categoria = datos.categoria;
    this.prioridad = datos.prioridad;
    this.descripcion = datos.descripcion;
    this.estado = datos.estado;
    this.autor = datos.autor;
    this.creada = new Date(datos.creada_en); // el JSON trae la fecha como texto
  }

  /** @returns {boolean} Urgente = prioridad alta y todavía no resuelta. */
  get esUrgente() {
    return this.prioridad === 'alta' && this.estado !== 'resuelta';
  }
}
