import { describe, expect, it } from 'vitest';
import {
    combineLocalDateAndTime,
    toApiDateTime,
    toLocalDateString,
} from '../../resources/js/utils/datetime';

describe('toLocalDateString', () => {
    it('devuelve el dia LOCAL, no el de UTC', () => {
        // Este es el bug que sustituye: toISOString().split('T')[0] sobre una
        // fecha a medianoche local devuelve el dia ANTERIOR en husos negativos,
        // porque convierte a UTC antes de cortar. La reserva se creaba con un
        // dia de desfase.
        const medianocheLocal = new Date(2026, 8, 5, 0, 0, 0);

        expect(toLocalDateString(medianocheLocal)).toBe('2026-09-05');
    });

    it('rellena mes y dia con ceros', () => {
        expect(toLocalDateString(new Date(2026, 0, 3))).toBe('2026-01-03');
    });

    it('no se desplaza en el ultimo instante del dia', () => {
        expect(toLocalDateString(new Date(2026, 8, 5, 23, 59, 59))).toBe('2026-09-05');
    });
});

describe('toApiDateTime', () => {
    it('produce el mismo instante para un Date y su equivalente en texto', () => {
        const fecha = new Date(2026, 8, 5, 10, 0, 0);

        expect(toApiDateTime(fecha)).toBe(toApiDateTime(fecha.toISOString()));
    });

    it('conserva el instante, no la hora de pared', () => {
        const fecha = new Date('2026-09-05T14:00:00Z');

        expect(toApiDateTime(fecha)).toBe('2026-09-05T14:00:00.000Z');
    });

    it('rechaza una fecha no interpretable en vez de propagar NaN', () => {
        expect(() => toApiDateTime('no-es-una-fecha')).toThrow(TypeError);
    });
});

describe('combineLocalDateAndTime', () => {
    it('combina fecha y hora en el huso local', () => {
        const resultado = combineLocalDateAndTime(new Date(2026, 8, 5), '10:30:00');

        expect(resultado.getFullYear()).toBe(2026);
        expect(resultado.getMonth()).toBe(8);
        expect(resultado.getDate()).toBe(5);
        expect(resultado.getHours()).toBe(10);
        expect(resultado.getMinutes()).toBe(30);
    });
});
