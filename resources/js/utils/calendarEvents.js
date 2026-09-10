import { isLabReservation, isPending } from './reservationStatus';

/**
 * Colores de los eventos del calendario de disponibilidad.
 */
export const EVENT_COLORS = Object.freeze({
    equipment: { background: '#ef4444', border: '#dc2626' },
    lab: { background: '#7c3aed', border: '#6d28d9' },
    pendingLab: { background: '#a78bfa', border: '#7c3aed' },
});

/**
 * Convierte reservas de la API en eventos de FullCalendar.
 *
 * El calendario puede mostrar la ocupación de un equipo o la de un
 * laboratorio completo, y en ambos casos mezcla reservas de los dos tipos:
 * una clase bloquea todos los equipos, y un equipo reservado impide apartar
 * el laboratorio. El título dice qué es cada bloque para que el usuario
 * entienda por qué no puede seleccionar ahí.
 *
 * @param {Array<object>} reservations
 * @param {{ context?: 'equipment' | 'lab' }} options
 */
export function mapReservationsToEvents(reservations, { context = 'equipment' } = {}) {
    if (!Array.isArray(reservations)) {
        return [];
    }

    return reservations.map((reservation) => {
        const lab = isLabReservation(reservation);
        const pending = isPending(reservation);
        const palette = lab
            ? (pending ? EVENT_COLORS.pendingLab : EVENT_COLORS.lab)
            : EVENT_COLORS.equipment;

        return {
            id: String(reservation.id),
            title: eventTitle(reservation, { lab, pending, context }),
            start: reservation.start_time,
            end: reservation.end_time,
            backgroundColor: palette.background,
            borderColor: palette.border,
            textColor: '#ffffff',
            classNames: [lab ? 'lab-reservation-event' : 'reservation-event'],
            extendedProps: {
                reservationId: reservation.id,
                type: reservation.type,
                status: reservation.status,
                purpose: reservation.purpose ?? null,
            },
        };
    });
}

/**
 * Horario de apertura -> businessHours de FullCalendar. Sin horario
 * configurado devuelve null (sin restricción); con horario, un bloque por
 * día activo. FullCalendar usa 0 = domingo, igual que la API.
 *
 * @param {{ weekday: number, opens_at: string, closes_at: string }[]} openingHours
 */
export function openingHoursToBusinessHours(openingHours) {
    if (!Array.isArray(openingHours) || openingHours.length === 0) {
        return null;
    }

    return openingHours.map((row) => ({
        daysOfWeek: [row.weekday],
        startTime: row.opens_at,
        endTime: row.closes_at,
    }));
}

/**
 * Cierres -> eventos de fondo no seleccionables.
 *
 * @param {{ id: number, starts_at: string, ends_at: string, reason: string, is_global: boolean }[]} closures
 */
export function closuresToEvents(closures) {
    if (!Array.isArray(closures)) {
        return [];
    }

    return closures.map((closure) => ({
        id: `closure-${closure.id}`,
        title: closure.is_global ? `Cerrado: ${closure.reason}` : `Cierre: ${closure.reason}`,
        start: closure.starts_at,
        end: closure.ends_at,
        display: 'background',
        backgroundColor: '#9ca3af',
        classNames: ['closure-event'],
        extendedProps: { type: 'closure', reason: closure.reason },
    }));
}

function eventTitle(reservation, { lab, pending, context }) {
    if (lab) {
        const prefix = pending ? 'Clase (pendiente)' : 'Clase';

        return reservation.purpose ? `${prefix}: ${reservation.purpose}` : prefix;
    }

    if (context === 'lab') {
        const identifier = reservation.equipment?.identifier;

        return identifier ? `Equipo reservado: ${identifier}` : 'Equipo reservado';
    }

    return 'Reservado';
}
