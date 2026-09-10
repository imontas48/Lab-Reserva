import { describe, expect, it } from 'vitest';
import { closuresToEvents, openingHoursToBusinessHours } from '../../resources/js/utils/calendarEvents';

describe('openingHoursToBusinessHours', () => {
    it('sin horario no hay restriccion', () => {
        expect(openingHoursToBusinessHours([])).toBeNull();
        expect(openingHoursToBusinessHours(undefined)).toBeNull();
    });

    it('traduce cada dia a un bloque de FullCalendar', () => {
        const result = openingHoursToBusinessHours([
            { weekday: 1, opens_at: '08:00', closes_at: '20:00' },
            { weekday: 6, opens_at: '09:00', closes_at: '13:00' },
        ]);

        expect(result).toEqual([
            { daysOfWeek: [1], startTime: '08:00', endTime: '20:00' },
            { daysOfWeek: [6], startTime: '09:00', endTime: '13:00' },
        ]);
    });
});

describe('closuresToEvents', () => {
    it('pinta los cierres como fondo y distingue los globales', () => {
        const [global, propio] = closuresToEvents([
            { id: 1, starts_at: '2026-09-15T00:00:00+00:00', ends_at: '2026-09-16T00:00:00+00:00', reason: 'Festivo', is_global: true },
            { id: 2, starts_at: '2026-09-17T08:00:00+00:00', ends_at: '2026-09-17T12:00:00+00:00', reason: 'Mantenimiento', is_global: false },
        ]);

        expect(global.display).toBe('background');
        expect(global.title).toBe('Cerrado: Festivo');
        expect(global.id).toBe('closure-1');
        expect(propio.title).toBe('Cierre: Mantenimiento');
        expect(propio.extendedProps.type).toBe('closure');
    });

    it('tolera entradas no validas', () => {
        expect(closuresToEvents(null)).toEqual([]);
    });
});
