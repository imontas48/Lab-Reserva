import { describe, expect, it } from 'vitest';
import {
    daySeries, hourSeries, statusSeries, weekdaySeries,
} from '../../resources/js/utils/reportCharts';

describe('reportCharts', () => {
    it('formatea las horas con dos digitos', () => {
        const { labels, values } = hourSeries([{ hour: 8, reservations: 3 }, { hour: 14, reservations: 1 }]);

        expect(labels).toEqual(['08:00', '14:00']);
        expect(values).toEqual([3, 1]);
    });

    it('ordena la semana de lunes a domingo y rellena los dias sin datos', () => {
        const { labels, values } = weekdaySeries([{ weekday: 0, reservations: 2 }, { weekday: 3, reservations: 5 }]);

        expect(labels).toEqual(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']);
        expect(values).toEqual([0, 0, 5, 0, 0, 0, 2]);
    });

    it('acorta la fecha al mes y dia y elige la metrica', () => {
        const rows = [{ date: '2026-09-08', reservations: 4, hours: 6.5 }];

        expect(daySeries(rows, 'hours')).toEqual({ labels: ['09-08'], values: [6.5] });
        expect(daySeries(rows, 'reservations').values).toEqual([4]);
    });

    it('omite los estados vacios y ordena de mayor a menor', () => {
        const { labels, values } = statusSeries({ pending: 0, confirmed: 2, no_show: 5, cancelled: 1 });

        expect(labels).toEqual(['Inasistencias', 'Confirmadas', 'Canceladas']);
        expect(values).toEqual([5, 2, 1]);
    });

    it('tolera entradas ausentes', () => {
        expect(hourSeries(undefined).values).toEqual([]);
        expect(statusSeries(null).labels).toEqual([]);
    });
});
