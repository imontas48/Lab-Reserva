/**
 * Cuadrícula del plano de un laboratorio a partir del tamaño y de la
 * posición de cada equipo.
 */

export const STATUS_STYLES = Object.freeze({
    available: { label: 'Disponible', classes: 'bg-green-100 border-green-400 text-green-900 dark:bg-green-900/40 dark:border-green-600 dark:text-green-100' },
    in_use: { label: 'En uso', classes: 'bg-blue-100 border-blue-400 text-blue-900 dark:bg-blue-900/40 dark:border-blue-500 dark:text-blue-100' },
    reserved: { label: 'Reservado próximamente', classes: 'bg-yellow-100 border-yellow-400 text-yellow-900 dark:bg-yellow-900/40 dark:border-yellow-500 dark:text-yellow-100' },
    out_of_service: { label: 'Fuera de servicio', classes: 'bg-red-100 border-red-400 text-red-900 dark:bg-red-900/40 dark:border-red-500 dark:text-red-100' },
});

/**
 * @param {{ grid_rows: number, grid_cols: number }} lab
 * @param {Array<{ id: number, grid_row: number|null, grid_col: number|null }>} equipment
 * @returns {{ rows: Array<Array<object|null>>, unplaced: Array<object> }}
 */
export function buildGrid(lab, equipment) {
    const rows = Math.max(0, Number(lab?.grid_rows) || 0);
    const cols = Math.max(0, Number(lab?.grid_cols) || 0);
    const list = Array.isArray(equipment) ? equipment : [];

    const grid = Array.from({ length: rows }, () => Array.from({ length: cols }, () => null));
    const unplaced = [];

    for (const item of list) {
        const r = item.grid_row;
        const c = item.grid_col;
        const placed = r !== null && c !== null && r >= 1 && r <= rows && c >= 1 && c <= cols;

        if (placed && grid[r - 1][c - 1] === null) {
            grid[r - 1][c - 1] = item;
        } else {
            unplaced.push(item);
        }
    }

    return { rows: grid, unplaced };
}

/**
 * Cuenta los puestos por estado, para la leyenda.
 *
 * @param {Array<{ status?: { status: string } }>} equipment
 */
export function countByStatus(equipment) {
    const counts = Object.fromEntries(Object.keys(STATUS_STYLES).map((k) => [k, 0]));

    for (const item of Array.isArray(equipment) ? equipment : []) {
        const key = item.status?.status;
        if (key in counts) counts[key] += 1;
    }

    return counts;
}
