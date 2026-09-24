/**
 * Prefijo de ruta bajo el que se sirve la SPA.
 *
 * En local la aplicación vive en la raíz del dominio y el prefijo es vacío. En
 * un despliegue compartido cuelga de un subdirectorio (por ejemplo
 * https://portal.ejemplo.edu/lab-reserva), y tanto Vue Router como el cliente
 * HTTP tienen que anteponer ese segmento; si no, el router no reconocería la
 * URL inicial y la API se pediría al sitio que ocupa la raíz.
 *
 * Se fija en tiempo de compilación (VITE_BASE_PATH) porque Vite también
 * necesita el prefijo para resolver sus chunks, y así hay una sola fuente.
 */
const raw = import.meta.env.VITE_BASE_PATH || '';

/** Prefijo sin barra final: '' o '/lab-reserva'. */
export const basePath = raw.replace(/\/+$/, '');

/** Base para Vue Router, siempre con barra final: '/' o '/lab-reserva/'. */
export const routerBase = `${basePath}/`;
