import { describe, expect, it } from 'vitest';
import {
    canMarkNoShow,
    cancelActionLabel,
    isCancellable,
    isInSeries,
    labNameOf,
    statusClasses,
    statusLabel,
    targetLabel,
} from '../../resources/js/utils/reservationStatus';

const equipmentReservation = (overrides = {}) => ({
    id: 1,
    type: 'equipment',
    status: 'confirmed',
    equipment_id: 7,
    equipment: { identifier: 'PC-07', lab: { name: 'Laboratorio de Redes' } },
    is_future: true,
    is_active: false,
    is_past: false,
    ...overrides,
});

const labReservation = (overrides = {}) => ({
    id: 2,
    type: 'lab',
    status: 'pending',
    lab_id: 3,
    lab: { name: 'Laboratorio de Redes' },
    purpose: 'Examen final',
    is_future: true,
    is_active: false,
    is_past: false,
    ...overrides,
});

describe('statusLabel', () => {
    it('describe una confirmada por su momento', () => {
        expect(statusLabel(equipmentReservation())).toBe('Programada');
        expect(statusLabel(equipmentReservation({ is_future: false, is_active: true, checked_in_at: '2026-09-10T10:00:00Z' }))).toBe('En uso');
        expect(statusLabel(equipmentReservation({ is_future: false, is_past: true }))).toBe('Completada');
    });

    it('describe los estados del flujo de aprobacion por si mismos', () => {
        expect(statusLabel(labReservation())).toBe('Pendiente de aprobación');
        expect(statusLabel(labReservation({ status: 'rejected' }))).toBe('Rechazada');
        expect(statusLabel(labReservation({ status: 'expired' }))).toBe('Expirada');
        expect(statusLabel(equipmentReservation({ status: 'cancelled' }))).toBe('Cancelada');
    });

    it('cada estado tiene clases distintas', () => {
        const pendiente = statusClasses(labReservation());
        const rechazada = statusClasses(labReservation({ status: 'rejected' }));
        const programada = statusClasses(equipmentReservation());

        expect(pendiente).not.toBe(rechazada);
        expect(pendiente).not.toBe(programada);
        expect(pendiente).toContain('amber');
    });
});

describe('targetLabel', () => {
    it('nombra el equipo y su laboratorio', () => {
        expect(targetLabel(equipmentReservation())).toBe('PC-07 · Laboratorio de Redes');
    });

    it('marca la reserva de laboratorio completo', () => {
        expect(targetLabel(labReservation())).toBe('Laboratorio de Redes (laboratorio completo)');
    });

    it('no falla si las relaciones no vienen cargadas', () => {
        expect(targetLabel(equipmentReservation({ equipment: undefined }))).toBe('Equipo #7');
        expect(targetLabel(labReservation({ lab: undefined }))).toBe('Laboratorio #3 (laboratorio completo)');
    });

    it('labNameOf resuelve el laboratorio de ambos tipos', () => {
        expect(labNameOf(equipmentReservation())).toBe('Laboratorio de Redes');
        expect(labNameOf(labReservation())).toBe('Laboratorio de Redes');
        expect(labNameOf({})).toBeNull();
    });
});

describe('asistencia y series', () => {
    it('una inasistencia tiene su propia etiqueta', () => {
        expect(statusLabel(equipmentReservation({ status: 'no_show', is_future: false, is_past: true }))).toBe('No asistió');
        expect(statusClasses(equipmentReservation({ status: 'no_show' }))).toContain('orange');
    });

    it('una reserva en curso sin llegada lo indica', () => {
        expect(statusLabel(equipmentReservation({ is_future: false, is_active: true }))).toBe('En curso (sin check-in)');
        expect(statusLabel(equipmentReservation({ is_future: false, is_active: true, checked_in_at: '2026-09-10T10:00:00Z' }))).toBe('En uso');
    });

    it('solo se marca inasistencia a una confirmada ya empezada sin llegada', () => {
        expect(canMarkNoShow(equipmentReservation({ is_future: false, is_active: true }))).toBe(true);
        expect(canMarkNoShow(equipmentReservation())).toBe(false);
        expect(canMarkNoShow(equipmentReservation({ is_future: false, is_active: true, checked_in_at: 'x' }))).toBe(false);
        expect(canMarkNoShow(labReservation({ is_future: false }))).toBe(false);
    });

    it('detecta las series', () => {
        expect(isInSeries(labReservation({ recurrence_group: 'abc' }))).toBe(true);
        expect(isInSeries(labReservation())).toBe(false);
    });
});

describe('isCancellable', () => {
    it('solo pendientes y confirmadas futuras', () => {
        expect(isCancellable(equipmentReservation())).toBe(true);
        expect(isCancellable(labReservation())).toBe(true);
        expect(isCancellable(equipmentReservation({ is_future: false, is_active: true }))).toBe(false);
        expect(isCancellable(labReservation({ status: 'rejected' }))).toBe(false);
        expect(isCancellable(equipmentReservation({ status: 'cancelled' }))).toBe(false);
    });

    it('una solicitud se retira, una reserva se cancela', () => {
        expect(cancelActionLabel(labReservation())).toBe('Retirar solicitud');
        expect(cancelActionLabel(equipmentReservation())).toBe('Cancelar reserva');
    });
});
