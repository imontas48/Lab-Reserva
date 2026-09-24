/**
 * Transformaciones de los datos de reportes a series de gráfica.
 *
 * Paleta de referencia del sistema (validada para daltonismo): un solo tono
 * azul para magnitudes; el resto de tonos solo se usa cuando la serie ES el
 * sujeto (identidad), nunca por rango.
 */
export const CHART_PALETTE = Object.freeze({
    light: {
        series1: '#2a78d6',
        series2: '#eb6834',
        muted: '#898781',
        grid: '#e1e0d9',
        axis: '#c3c2b7',
        surface: '#fcfcfb',
        text: '#52514e',
    },
    dark: {
        series1: '#3987e5',
        series2: '#d95926',
        muted: '#898781',
        grid: '#2c2c2a',
        axis: '#383835',
        surface: '#1a1a19',
        text: '#c3c2b7',
    },
});

export const WEEKDAY_LABELS = Object.freeze(['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']);

export const STATUS_LABELS = Object.freeze({
    pending: 'Pendientes',
    confirmed: 'Confirmadas',
    completed: 'Completadas',
    cancelled: 'Canceladas',
    rejected: 'Rechazadas',
    expired: 'Expiradas',
    no_show: 'Inasistencias',
});

/**
 * @param {{ hour: number, reservations: number }[]} byHour
 */
export function hourSeries(byHour) {
    const rows = Array.isArray(byHour) ? byHour : [];

    return {
        labels: rows.map((r) => `${String(r.hour).padStart(2, '0')}:00`),
        values: rows.map((r) => r.reservations),
    };
}

/**
 * Reordena de lunes a domingo, que es como se lee una semana lectiva.
 *
 * @param {{ weekday: number, reservations: number }[]} byWeekday
 */
export function weekdaySeries(byWeekday) {
    const rows = Array.isArray(byWeekday) ? byWeekday : [];
    const order = [1, 2, 3, 4, 5, 6, 0];
    const byIndex = Object.fromEntries(rows.map((r) => [r.weekday, r.reservations]));

    return {
        labels: order.map((d) => WEEKDAY_LABELS[d]),
        values: order.map((d) => byIndex[d] ?? 0),
    };
}

/**
 * @param {{ date: string, reservations: number, hours: number }[]} byDay
 * @param {'reservations' | 'hours'} metric
 */
export function daySeries(byDay, metric = 'hours') {
    const rows = Array.isArray(byDay) ? byDay : [];

    return {
        labels: rows.map((r) => r.date.slice(5)),
        values: rows.map((r) => r[metric]),
    };
}

/**
 * Solo los estados con datos, de mayor a menor, para no pintar barras vacías.
 *
 * @param {Record<string, number>} byStatus
 */
export function statusSeries(byStatus) {
    const entries = Object.entries(byStatus ?? {})
        .filter(([, count]) => count > 0)
        .sort((a, b) => b[1] - a[1]);

    return {
        labels: entries.map(([status]) => STATUS_LABELS[status] ?? status),
        values: entries.map(([, count]) => count),
    };
}
