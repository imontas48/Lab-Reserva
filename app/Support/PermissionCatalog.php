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
     * teacher y student coinciden hoy: la diferencia entre ambos no estaba
     * implementada en ninguna parte. Ahora es un cambio de datos, no de codigo.
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
        ],
        'student' => [
            'labs.viewAny', 'labs.view',
            'equipment.viewAny', 'equipment.view',
            'software.viewAny', 'software.view',
            'reservations.create', 'reservations.view',
            'reservations.update', 'reservations.cancel',
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
            'description' => 'Consulta el catálogo y gestiona sus propias reservas.',
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
