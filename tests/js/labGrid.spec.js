import { describe, expect, it } from 'vitest';
import { buildGrid, countByStatus } from '../../resources/js/utils/labGrid';

const pc = (id, row, col, status = 'available') => ({
    id, identifier: `PC-${id}`, grid_row: row, grid_col: col, status: { status },
});

describe('buildGrid', () => {
    it('coloca cada equipo en su celda y deja el resto vacio', () => {
        const { rows, unplaced } = buildGrid({ grid_rows: 2, grid_cols: 2 }, [pc(1, 1, 1), pc(2, 2, 2)]);

        expect(rows[0][0].id).toBe(1);
        expect(rows[0][1]).toBeNull();
        expect(rows[1][1].id).toBe(2);
        expect(unplaced).toEqual([]);
    });

    it('los equipos sin posicion o fuera de la cuadricula quedan aparte', () => {
        const { rows, unplaced } = buildGrid({ grid_rows: 1, grid_cols: 1 }, [pc(1, null, null), pc(2, 3, 3), pc(3, 1, 1)]);

        expect(rows[0][0].id).toBe(3);
        expect(unplaced.map((e) => e.id)).toEqual([1, 2]);
    });

    it('si dos equipos reclaman la misma celda, el segundo queda sin colocar', () => {
        const { unplaced } = buildGrid({ grid_rows: 1, grid_cols: 1 }, [pc(1, 1, 1), pc(2, 1, 1)]);

        expect(unplaced.map((e) => e.id)).toEqual([2]);
    });

    it('sin cuadricula no hay filas', () => {
        expect(buildGrid({ grid_rows: 0, grid_cols: 0 }, [pc(1, 1, 1)]).rows).toEqual([]);
        expect(buildGrid(null, undefined).unplaced).toEqual([]);
    });
});

describe('countByStatus', () => {
    it('cuenta los puestos por estado e ignora los desconocidos', () => {
        const counts = countByStatus([pc(1, 1, 1), pc(2, 1, 2, 'in_use'), pc(3, 1, 3, 'in_use'), { id: 4, status: { status: 'raro' } }]);

        expect(counts).toEqual({ available: 1, in_use: 2, reserved: 0, out_of_service: 0 });
    });
});
