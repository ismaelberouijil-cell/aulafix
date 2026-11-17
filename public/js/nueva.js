/**
 * nueva.js — En nueva.php: contador de caracteres de la descripción.
 */
const MAXIMO = 500;
const area = document.getElementById('descripcion');
const contador = document.getElementById('contador-desc');

if (area && contador) {
  /** Escribe «N / 500 caracteres» y lo marca en rojo (clase CSS) si se pasa. */
  function actualizarContador() {
    const n = area.value.length;
    contador.textContent = `${n} / ${MAXIMO} caracteres`;
    contador.classList.toggle('excedido', n > MAXIMO);
  }

  area.addEventListener('input', actualizarContador);
  actualizarContador();
}
