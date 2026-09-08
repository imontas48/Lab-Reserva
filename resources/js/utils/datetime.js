/**
 * Conversión de fechas para el contrato con la API.
 *
 * El backend acepta ISO-8601 y normaliza al huso de la aplicación. El problema
 * estaba en el cliente: el calendario enviaba `selectInfo.startStr`, hora local
 * SIN offset, y el formulario rápido enviaba `new Date(...).toISOString()`, UTC
 * con sufijo Z. Los dos alimentaban el mismo campo del mismo endpoint, así que
 * reservar las 10:00 desde el calendario y desde el formulario producía
 * instantes distintos.
 *
 * Aquí hay una sola función para hablar con la API y otra para el caso de una
 * fecha suelta, que es donde estaba el off-by-one: `toISOString().split('T')[0]`
 * sobre un Date a medianoche local devuelve el día ANTERIOR en husos negativos,
 * porque convierte a UTC antes de cortar.
 */

/**
 * Instante -> ISO-8601 con offset, apto para enviar a la API.
 *
 * @param {Date|string|number} value
 * @returns {string}
 */
export function toApiDateTime(value) {
    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) {
        throw new TypeError(`Fecha no interpretable: ${value}`);
    }

    return date.toISOString();
}

/**
 * Fecha -> 'YYYY-MM-DD' en hora LOCAL.
 *
 * Sustituye a `toISOString().split('T')[0]`: con el usuario en UTC-4, la
 * medianoche local del día 5 es el día 4 a las 20:00 en UTC, de modo que ese
 * recorte devolvía el día 4 y la reserva se creaba con un día de desfase.
 *
 * @param {Date} date
 * @returns {string}
 */
export function toLocalDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

/**
 * Combina una fecha local y una hora 'HH:mm[:ss]' en un instante.
 *
 * @param {Date} date
 * @param {string} time
 * @returns {Date}
 */
export function combineLocalDateAndTime(date, time) {
    return new Date(`${toLocalDateString(date)}T${time}`);
}
