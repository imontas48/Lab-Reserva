/**
 * Etiquetas del registro de actividades.
 *
 * El backend guarda claves (event, subject_type, nombres de columna); aquí
 * vive la única traducción a español para la vista y su detalle.
 */

const EVENT_LABELS = Object.freeze({
    created: 'Creación',
    updated: 'Modificación',
    deleted: 'Eliminación',
    restored: 'Restauración',
    login: 'Inicio de sesión',
    login_failed: 'Inicio de sesión fallido',
    logout: 'Cierre de sesión',
    permissions_synced: 'Permisos de rol',
    software_synced: 'Software de equipo',
    opening_hours_synced: 'Horario de apertura',
    layout_updated: 'Plano del laboratorio',
    reservations_auto_completed: 'Reservas completadas (automático)',
    reservations_auto_expired: 'Solicitudes expiradas (automático)',
    report_exported: 'Exportación de reporte',
});

const EVENT_CLASSES = Object.freeze({
    created: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    updated: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    deleted: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    login_failed: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
});

const DEFAULT_EVENT_CLASS = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';

const SUBJECT_LABELS = Object.freeze({
    lab: 'Laboratorio',
    equipment: 'Equipo',
    software: 'Software',
    reservation: 'Reserva',
    user: 'Usuario',
    role: 'Rol',
    permission: 'Permiso',
    user_role: 'Rol individual',
    group_role_assignment: 'Regla de grupo',
    permission_override: 'Excepción de permiso',
    lab_closure: 'Cierre',
    academic_period: 'Periodo académico',
    incident: 'Incidencia',
    report: 'Reporte',
});

const FIELD_LABELS = Object.freeze({
    name: 'Nombre',
    email: 'Correo',
    password: 'Contraseña',
    role: 'Rol base',
    status: 'Estado',
    type: 'Tipo',
    description: 'Descripción',
    location: 'Ubicación',
    capacity: 'Capacidad',
    is_active: 'Activo',
    is_operational: 'Operativo',
    identifier: 'Identificador',
    specifications: 'Especificaciones',
    version: 'Versión',
    lab_id: 'Laboratorio (id)',
    equipment_id: 'Equipo (id)',
    user_id: 'Usuario (id)',
    role_id: 'Rol (id)',
    permission_id: 'Permiso (id)',
    start_time: 'Inicio',
    end_time: 'Fin',
    purpose: 'Motivo',
    rejection_reason: 'Motivo de rechazo',
    reviewed_by: 'Revisada por (id)',
    reviewed_at: 'Revisada el',
    check_in_code: 'Código de asistencia',
    checked_in_at: 'Asistencia registrada',
    no_show_at: 'Inasistencia registrada',
    no_show_count: 'Inasistencias',
    reservation_blocked_until: 'Bloqueado hasta',
    must_change_password: 'Contraseña temporal',
    deleted_at: 'Eliminado el',
    expires_at: 'Vence el',
    granted_by: 'Otorgado por (id)',
    reason: 'Motivo',
    starts_at: 'Desde',
    ends_at: 'Hasta',
    starts_on: 'Desde',
    ends_on: 'Hasta',
    severity: 'Gravedad',
    resolution: 'Resolución',
    resolved_by: 'Resuelta por (id)',
    resolved_at: 'Resuelta el',
    display_name: 'Nombre visible',
    added: 'Agregados',
    removed: 'Quitados',
    count: 'Cantidad',
    reservation_ids: 'Reservas (id)',
    grid: 'Cuadrícula',
    positions: 'Puestos ubicados',
    from: 'Desde',
    to: 'Hasta',
});

export const EVENT_OPTIONS = Object.freeze(
    Object.entries(EVENT_LABELS).map(([value, text]) => ({ value, text })),
);

export const SUBJECT_OPTIONS = Object.freeze(
    Object.entries(SUBJECT_LABELS).map(([value, text]) => ({ value, text })),
);

export function eventLabel(event) {
    return EVENT_LABELS[event] ?? event;
}

export function eventClass(event) {
    return EVENT_CLASSES[event] ?? DEFAULT_EVENT_CLASS;
}

export function subjectLabel(subjectType) {
    return SUBJECT_LABELS[subjectType] ?? subjectType ?? '—';
}

export function fieldLabel(field) {
    return FIELD_LABELS[field] ?? field;
}

export function formatValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'Sí' : 'No';
    if (Array.isArray(value)) return value.length ? value.join(', ') : '—';
    if (typeof value === 'object') return JSON.stringify(value);

    return String(value);
}

export function formatTimestamp(iso) {
    return iso
        ? new Date(iso).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'medium' })
        : '—';
}

/**
 * Filas del detalle a partir de `properties`, sea cual sea su forma:
 *  - { old, new }        cambio de campos (antes → después)
 *  - { attributes }      alta o baja (estado completo)
 *  - cualquier otra cosa contexto del evento (añadidos, cantidades…)
 *
 * @returns {Array<{ field: string, label: string, before: string|null, after: string }>}
 */
export function propertyRows(properties) {
    if (!properties) return [];

    if (properties.old || properties.new) {
        const fields = [...new Set([...Object.keys(properties.old ?? {}), ...Object.keys(properties.new ?? {})])];

        return fields.map((field) => ({
            field,
            label: fieldLabel(field),
            before: formatValue(properties.old?.[field]),
            after: formatValue(properties.new?.[field]),
        }));
    }

    const source = properties.attributes ?? properties;

    return Object.entries(source).map(([field, value]) => ({
        field,
        label: fieldLabel(field),
        before: null,
        after: formatValue(value),
    }));
}
