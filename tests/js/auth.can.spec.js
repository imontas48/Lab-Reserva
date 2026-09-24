import { beforeEach, describe, expect, it } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useAuthStore } from '../../resources/js/stores/auth';

/**
 * La interfaz decide que mostrar a partir de los permisos efectivos que
 * devuelve el backend, no del rol.
 */
describe('useAuthStore permisos', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('sin sesion no tiene permisos', () => {
        const store = useAuthStore();

        expect(store.permissions).toEqual([]);
        expect(store.can('reservations.createLab')).toBe(false);
        expect(store.canCreateLabReservation).toBe(false);
        expect(store.canApproveReservations).toBe(false);
    });

    it('un profesor puede solicitar laboratorios pero no aprobarlos', () => {
        const store = useAuthStore();
        store.user = {
            id: 1,
            role: 'teacher',
            permissions: ['reservations.create', 'reservations.createLab'],
        };

        expect(store.canCreateLabReservation).toBe(true);
        expect(store.canApproveReservations).toBe(false);
        expect(store.isTeacher).toBe(true);
    });

    it('quien tenga reservations.approve ve la cola aunque no sea admin por rol', () => {
        const store = useAuthStore();
        store.user = { id: 2, role: 'teacher', permissions: ['reservations.approve'] };

        expect(store.canApproveReservations).toBe(true);
        expect(store.isAdmin).toBe(false);
    });

    it('expone las cuotas de reserva del rol', () => {
        const store = useAuthStore();
        store.user = { id: 3, role: 'student', permissions: [], reservation_limits: { max_hours: 4 } };

        expect(store.reservationLimits).toEqual({ max_hours: 4 });
    });
});
