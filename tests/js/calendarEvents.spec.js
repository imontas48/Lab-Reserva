import { describe, expect, it } from 'vitest';
import { EVENT_COLORS, mapReservationsToEvents } from '../../resources/js/utils/calendarEvents';

const equipmentReservation = {
    id: 1,
    type: 'equipment',
    status: 'confirmed',
    start_time: '2026-09-10T10:00:00+00:00',
    end_time: '2026-09-10T11:00:00+00:00',
    equipment: { identifier: 'PC-01' },
};

const labReservation = {
    id: 2,
    type: 'lab',
    status: 'confirmed',
    purpose: 'Examen final',
    start_time: '2026-09-10T12:00:00+00:00',
    end_time: '2026-09-10T14:00:00+00:00',
};

describe('mapReservationsToEvents', () => {
    it('devuelve una lista vacia con entradas no validas', () => {
        expect(mapReservationsToEvents(null)).toEqual([]);
        expect(mapReservationsToEvents(undefined)).toEqual([]);
    });

    it('pinta una clase con su motivo y en su color', () => {
        const [event] = mapReservationsToEvents([labReservation]);

        expect(event.title).toBe('Clase: Examen final');
        expect(event.backgroundColor).toBe(EVENT_COLORS.lab.background);
        expect(event.extendedProps.type).toBe('lab');
    });

    it('distingue una clase pendiente de una aprobada', () => {
        const [event] = mapReservationsToEvents([{ ...labReservation, status: 'pending' }]);

        expect(event.title).toBe('Clase (pendiente): Examen final');
        expect(event.backgroundColor).toBe(EVENT_COLORS.pendingLab.background);
    });

    it('en el calendario de un equipo, sus reservas son "Reservado"', () => {
        const [event] = mapReservationsToEvents([equipmentReservation], { context: 'equipment' });

        expect(event.title).toBe('Reservado');
        expect(event.backgroundColor).toBe(EVENT_COLORS.equipment.background);
    });

    it('en el calendario de un laboratorio, los equipos reservados se identifican', () => {
        const [event] = mapReservationsToEvents([equipmentReservation], { context: 'lab' });

        expect(event.title).toBe('Equipo reservado: PC-01');
    });

    it('conserva inicio, fin e id como cadena', () => {
        const [event] = mapReservationsToEvents([equipmentReservation]);

        expect(event.id).toBe('1');
        expect(event.start).toBe(equipmentReservation.start_time);
        expect(event.end).toBe(equipmentReservation.end_time);
    });
});
