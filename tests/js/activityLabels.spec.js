import { describe, expect, it } from 'vitest';
import {
    EVENT_OPTIONS,
    eventLabel,
    formatValue,
    propertyRows,
    subjectLabel,
} from '../../resources/js/utils/activityLabels';

describe('activityLabels', () => {
    it('traduce eventos y sujetos, y deja pasar los desconocidos', () => {
        expect(eventLabel('login_failed')).toBe('Inicio de sesión fallido');
        expect(eventLabel('evento_nuevo')).toBe('evento_nuevo');
        expect(subjectLabel('lab')).toBe('Laboratorio');
        expect(subjectLabel(null)).toBe('—');
        expect(EVENT_OPTIONS.find((option) => option.value === 'created').text).toBe('Creación');
    });

    it('formatea valores vacíos, booleanos y listas', () => {
        expect(formatValue(null)).toBe('—');
        expect(formatValue(false)).toBe('No');
        expect(formatValue([])).toBe('—');
        expect(formatValue(['labs.view', 'labs.viewAny'])).toBe('labs.view, labs.viewAny');
        expect(formatValue(3)).toBe('3');
    });

    it('arma filas antes → después para un cambio', () => {
        const rows = propertyRows({
            old: { status: 'pending', password: '[oculto]' },
            new: { status: 'confirmed', password: '[oculto]' },
        });

        expect(rows).toEqual([
            { field: 'status', label: 'Estado', before: 'pending', after: 'confirmed' },
            { field: 'password', label: 'Contraseña', before: '[oculto]', after: '[oculto]' },
        ]);
    });

    it('muestra el estado completo de un alta y el contexto de otros eventos', () => {
        expect(propertyRows({ attributes: { name: 'Lab A' } })).toEqual([
            { field: 'name', label: 'Nombre', before: null, after: 'Lab A' },
        ]);
        expect(propertyRows({ added: ['labs.view'], removed: [] })).toEqual([
            { field: 'added', label: 'Agregados', before: null, after: 'labs.view' },
            { field: 'removed', label: 'Quitados', before: null, after: '—' },
        ]);
        expect(propertyRows(null)).toEqual([]);
    });
});
