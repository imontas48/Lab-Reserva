/**
 * Etiquetas y estilos de una reserva, únicos para toda la aplicación.
 *
 * Tres vistas mantenían su propia copia de `getStatusLabel` con matices
 * distintos ("En Uso" frente a "En Uso Ahora"), y ninguna conocía los estados
 * del flujo de aprobación. Aquí vive la única tabla; las vistas solo la leen.
 */

export const RESERVATION_STATUS = Object.freeze({
    PENDING: 'pending',
    CONFIRMED: 'confirmed',
    CANCELLED: 'cancelled',
    COMPLETED: 'completed',
    REJECTED: 'rejected',
    EXPIRED: 'expired',
    NO_SHOW: 'no_show',
});

export const RESERVATION_TYPE = Object.freeze({
    EQUIPMENT: 'equipment',
    LAB: 'lab',
});

const STATUS_LABELS = Object.freeze({
    pending: 'Pendiente de aprobación',
    cancelled: 'Cancelada',
    completed: 'Completada',
    rejected: 'Rechazada',
    expired: 'Expirada',
    no_show: 'No asistió',
});

const STATUS_CLASSES = Object.freeze({
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    completed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
    expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    no_show: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    active: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    upcoming: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    past: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
});

/**
 * Opciones para un <select> de filtro por estado.
 */
export const STATUS_FILTER_OPTIONS = Object.freeze([
    { value: 'pending', text: 'Pendientes' },
    { value: 'confirmed', text: 'Confirmadas' },
    { value: 'completed', text: 'Completadas' },
    { value: 'cancelled', text: 'Canceladas' },
    { value: 'rejected', text: 'Rechazadas' },
    { value: 'expired', text: 'Expiradas' },
    { value: 'no_show', text: 'Inasistencias' },
]);

export function isLabReservation(reservation) {
    return reservation?.type === RESERVATION_TYPE.LAB;
}

export function isPending(reservation) {
    return reservation?.status === RESERVATION_STATUS.PENDING;
}

/**
 * Una reserva confirmada se describe por su momento (programada, en uso,
 * completada); cualquier otro estado se describe por sí mismo.
 */
export function statusLabel(reservation) {
    if (!reservation) {
        return '';
    }

    if (reservation.status !== RESERVATION_STATUS.CONFIRMED) {
        return STATUS_LABELS[reservation.status] ?? reservation.status;
    }

    if (reservation.is_active) return reservation.checked_in_at ? 'En uso' : 'En curso (sin check-in)';
    if (reservation.is_future) return 'Programada';
    if (reservation.is_past) return 'Completada';

    return 'Confirmada';
}

/**
 * ¿Puede el usuario marcar una inasistencia a mano? Confirmada, ya
 * empezada, sin llegada registrada. El permiso lo decide el servidor.
 */
export function canMarkNoShow(reservation) {
    return reservation?.status === RESERVATION_STATUS.CONFIRMED
        && !reservation.checked_in_at
        && !reservation.is_future;
}

export function isInSeries(reservation) {
    return !!reservation?.recurrence_group;
}

export function statusClasses(reservation) {
    if (!reservation) {
        return '';
    }

    if (reservation.status !== RESERVATION_STATUS.CONFIRMED) {
        return STATUS_CLASSES[reservation.status] ?? STATUS_CLASSES.past;
    }

    if (reservation.is_active) return STATUS_CLASSES.active;
    if (reservation.is_future) return STATUS_CLASSES.upcoming;

    return STATUS_CLASSES.past;
}

/**
 * Qué se reservó, en una línea: "Laboratorio de Redes (laboratorio completo)"
 * o "PC-01 · Laboratorio de Redes".
 */
export function targetLabel(reservation) {
    if (!reservation) {
        return '';
    }

    if (isLabReservation(reservation)) {
        const name = reservation.lab?.name ?? `Laboratorio #${reservation.lab_id}`;

        return `${name} (laboratorio completo)`;
    }

    const equipment = reservation.equipment?.identifier ?? `Equipo #${reservation.equipment_id}`;
    const lab = reservation.equipment?.lab?.name;

    return lab ? `${equipment} · ${lab}` : equipment;
}

export function labNameOf(reservation) {
    return reservation?.lab?.name ?? reservation?.equipment?.lab?.name ?? null;
}

/**
 * Pendientes y confirmadas futuras se pueden cancelar (o retirar, si son
 * solicitudes). El servidor vuelve a comprobarlo.
 */
export function isCancellable(reservation) {
    if (!reservation?.is_future) {
        return false;
    }

    return [RESERVATION_STATUS.PENDING, RESERVATION_STATUS.CONFIRMED].includes(reservation.status);
}

export function cancelActionLabel(reservation) {
    return isPending(reservation) ? 'Retirar solicitud' : 'Cancelar reserva';
}
