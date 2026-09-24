<?php

namespace App\Support;

/**
 * Catalogo de permisos del sistema y su reparto por rol base.
 *
 * Es la unica fuente de verdad: de aqui salen el seeder que puebla las tablas y
 * las comprobaciones de las policies. Antes no existia ningun seeder para
 * permissions, roles ni role_permissions, de modo que en una instalacion limpia
 * resolveEffectivePermissions() devolvia siempre una lista vacia.
 *
 * Las acciones se llaman igual que los metodos de las policies (viewAny, view,
 * create, update, delete, cancel) para que la correspondencia sea mecanica y no
 * haya que mantener una tabla de traduccion.
 */
class PermissionCatalog
{
    /**
     * subject => [action => descripcion]
     *
     * @var array<string, array<string, string>>
     */
    public const CATALOG = [
        'labs' => [
            'viewAny' => 'Listar laboratorios',
            'view' => 'Ver el detalle de un laboratorio',
            'create' => 'Crear laboratorios',
            'update' => 'Modificar laboratorios',
            'delete' => 'Eliminar laboratorios',
        ],
        'equipment' => [
            'viewAny' => 'Listar equipos',
            'view' => 'Ver el detalle de un equipo',
            'create' => 'Registrar equipos',
            'update' => 'Modificar equipos',
            'delete' => 'Eliminar equipos',
        ],
        'software' => [
            'viewAny' => 'Listar software',
            'view' => 'Ver el detalle de un software',
            'create' => 'Registrar software',
            'update' => 'Modificar software',
            'delete' => 'Eliminar software',
        ],
        'reservations' => [
            'viewAny' => 'Listar las reservas de cualquier usuario',
            'view' => 'Ver el detalle de una reserva propia',
            'create' => 'Crear reservas',
            'update' => 'Modificar una reserva propia',
            'cancel' => 'Cancelar una reserva propia',
            'delete' => 'Eliminar reservas',
            'createLab' => 'Reservar un laboratorio completo para una clase',
            'approve' => 'Aprobar o rechazar solicitudes de laboratorio completo',
        ],
        'schedule' => [
            'view' => 'Consultar horarios, cierres y periodos académicos',
            'manage' => 'Gestionar horarios de apertura, cierres y periodos académicos',
        ],
        'users' => [
            'viewAny' => 'Listar usuarios',
            'view' => 'Ver el detalle de un usuario',
            'create' => 'Dar de alta usuarios con contrasena temporal',
            'update' => 'Modificar usuarios y su rol base',
            'delete' => 'Dar de baja usuarios',
        ],
        'reports' => [
            'view' => 'Consultar reportes de uso y exportar datos',
        ],
        'incidents' => [
            'create' => 'Reportar incidencias sobre un equipo',
            'viewAny' => 'Ver todas las incidencias',
            'update' => 'Atender y resolver incidencias',
        ],
    ];

    /**
     * Permisos de cada rol base, expresados como "subject.action".
     *
     * El reparto reproduce exactamente el comportamiento anterior al cableado:
     * el catalogo de laboratorios, equipos y software es consultable por
     * cualquier autenticado (los estudiantes lo necesitan para reservar) y su
     * administracion es exclusiva del administrador.
     *
     * La unica diferencia entre teacher y student es reservations.createLab:
     * el profesor puede apartar un laboratorio completo para dar una clase.
     * Aprobar esas solicitudes (reservations.approve) es exclusivo del
     * administrador.
     *
     * @var array<string, array<int, string>|string>
     */
    public const ROLE_PERMISSIONS = [
        'admin' => '*',
        'teacher' => [
            'labs.viewAny', 'labs.view',
            'equipment.viewAny', 'equipment.view',
            'software.viewAny', 'software.view',
            'reservations.create', 'reservations.view',
            'reservations.update', 'reservations.cancel',
            'reservations.createLab',
            'schedule.view',
            'incidents.create',
        ],
        'student' => [
            'labs.viewAny', 'labs.view',
            'equipment.viewAny', 'equipment.view',
            'software.viewAny', 'software.view',
            'reservations.create', 'reservations.view',
            'reservations.update', 'reservations.cancel',
            'schedule.view',
            'incidents.create',
        ],
    ];

    /**
     * Metadatos de los roles base, que se corresponden con el ENUM users.role.
     *
     * @var array<string, array{display_name: string, description: string, color: string}>
     */
    public const BASE_ROLES = [
        'admin' => [
            'display_name' => 'Administrador',
            'description' => 'Control total del sistema y de su configuración.',
            'color' => 'red',
        ],
        'teacher' => [
            'display_name' => 'Profesor',
            'description' => 'Consulta el catálogo, gestiona sus reservas y solicita laboratorios completos para sus clases.',
            'color' => 'blue',
        ],
        'student' => [
            'display_name' => 'Estudiante',
            'description' => 'Consulta el catálogo y gestiona sus propias reservas.',
            'color' => 'green',
        ],
    ];

    /**
     * Todas las claves "subject.action" del catalogo.
     *
     * @return array<int, string>
     */
    public static function allKeys(): array
    {
        $keys = [];

        foreach (self::CATALOG as $subject => $actions) {
            foreach (array_keys($actions) as $action) {
                $keys[] = "{$subject}.{$action}";
            }
        }

        return $keys;
    }

    /**
     * Claves que corresponden a un rol base, resolviendo el comodin.
     *
     * @return array<int, string>
     */
    public static function keysForRole(string $role): array
    {
        $spec = self::ROLE_PERMISSIONS[$role] ?? [];

        return $spec === '*' ? self::allKeys() : $spec;
    }
}
