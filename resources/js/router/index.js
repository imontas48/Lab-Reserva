import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { routerBase } from '@/utils/basePath';

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONFIGURACIÓN DE VUE ROUTER
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Sistema de enrutamiento de la aplicación con protección de rutas.
 *
 * CARACTERÍSTICAS:
 * - Rutas públicas (login, register)
 * - Rutas protegidas (dashboard, etc.) que requieren autenticación
 * - Rutas específicas por rol (admin, teacher, student)
 * - Navigation Guards para control de acceso
 * - Lazy loading de componentes para optimizar la carga
 *
 * ESTRUCTURA:
 * - AuthLayout: Envuelve las páginas de autenticación (login, register)
 * - AppLayout: Envuelve las páginas protegidas (dashboard, etc.)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

const routes = [
    // =========================================================================
    // RUTAS PÚBLICAS (Sin autenticación)
    // =========================================================================

    /**
     * Página de inicio / Landing
     * Redirige a dashboard si está autenticado, a login si no
     */
    {
        path: '/',
        name: 'home',
        // Sin redirect(): las redirecciones declaradas se resuelven en
        // router.resolve(), ANTES de que beforeEach restaure la sesion, asi
        // que en un arranque en frio isAuthenticated era siempre false y la
        // navegacion iba a /login para rebotar acto seguido a /dashboard.
        // El guard decide, que es quien sabe si hay sesion.
        redirect: { name: 'dashboard' },
    },

    /**
     * Rutas de autenticación (Login, Register)
     * Usan AuthLayout que es un diseño minimalista para auth
     */
    {
        path: '/auth',
        component: () => import('@/layouts/AuthLayout.vue'),
        meta: { guest: true }, // Solo accesible si NO está autenticado
        children: [
            {
                path: '/login',
                name: 'login',
                component: () => import('@/views/auth/LoginView.vue'),
                meta: {
                    title: 'Iniciar Sesión',
                    guest: true,
                }
            },
            {
                path: '/register',
                name: 'register',
                component: () => import('@/views/auth/RegisterView.vue'),
                meta: {
                    title: 'Crear Cuenta',
                    guest: true,
                }
            },
            {
                path: '/forgot-password',
                name: 'password.forgot',
                component: () => import('@/views/auth/ForgotPasswordView.vue'),
                meta: {
                    title: 'Recuperar Contraseña',
                    guest: true,
                }
            },
            {
                path: '/reset-password',
                name: 'password.reset',
                component: () => import('@/views/auth/ResetPasswordView.vue'),
                meta: {
                    title: 'Nueva Contraseña',
                    guest: true,
                }
            },
        ]
    },

    /**
     * Cambio de contraseña obligatorio. Requiere sesión pero usa AuthLayout:
     * el usuario con contraseña temporal no debe ver el menú de la aplicación,
     * porque el backend le rechaza todo lo que no sea este cambio.
     */
    {
        path: '/change-password',
        component: () => import('@/layouts/AuthLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'password.change',
                component: () => import('@/views/auth/ChangePasswordView.vue'),
                meta: {
                    title: 'Elige tu contraseña',
                    requiresAuth: true,
                }
            },
        ]
    },

    // =========================================================================
    // RUTAS PROTEGIDAS (Requieren autenticación)
    // =========================================================================

    /**
     * Rutas de la aplicación principal
     * Usan AppLayout que incluye navbar, sidebar, etc.
     */
    {
        path: '/app',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '/dashboard',
                name: 'dashboard',
                component: () => import('@/views/DashboardView.vue'),
                meta: {
                    title: 'Dashboard',
                    requiresAuth: true,
                }
            },

            // ─────────────────────────────────────────────────────────────────
            // LABORATORIOS
            // ─────────────────────────────────────────────────────────────────
            {
                path: '/labs',
                name: 'labs.index',
                component: () => import('@/views/labs/LabsIndexView.vue'),
                meta: {
                    title: 'Laboratorios',
                    requiresAuth: true,
                }
            },
            {
                path: '/labs/:id',
                name: 'labs.show',
                component: () => import('@/views/labs/LabsShowView.vue'),
                meta: {
                    title: 'Detalle de Laboratorio',
                    requiresAuth: true,
                }
            },
            {
                path: '/labs/create',
                name: 'labs.create',
                component: () => import('@/views/labs/LabsCreateEditView.vue'),
                meta: {
                    title: 'Crear Laboratorio',
                    requiresAuth: true,
                    requiresAdmin: true, // Solo administradores
                }
            },
            {
                path: '/labs/:id/edit',
                name: 'labs.edit',
                component: () => import('@/views/labs/LabsCreateEditView.vue'),
                meta: {
                    title: 'Editar Laboratorio',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },

            // ─────────────────────────────────────────────────────────────────
            // EQUIPOS
            // ─────────────────────────────────────────────────────────────────
            {
                path: '/equipment',
                name: 'equipment.index',
                component: () => import('@/views/equipment/EquipmentIndexView.vue'),
                meta: {
                    title: 'Equipos',
                    requiresAuth: true,
                }
            },
            {
                path: '/equipment/:id',
                name: 'equipment.show',
                component: () => import('@/views/equipment/EquipmentShowView.vue'),
                meta: {
                    title: 'Detalle de Equipo',
                    requiresAuth: true,
                }
            },
            {
                path: '/equipment/create',
                name: 'equipment.create',
                component: () => import('@/views/equipment/EquipmentCreateEditView.vue'),
                meta: {
                    title: 'Registrar Equipo',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },
            {
                path: '/equipment/:id/edit',
                name: 'equipment.edit',
                component: () => import('@/views/equipment/EquipmentCreateEditView.vue'),
                meta: {
                    title: 'Editar Equipo',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },

            // ─────────────────────────────────────────────────────────────────
            // SOFTWARE
            // ─────────────────────────────────────────────────────────────────
            {
                path: '/software',
                name: 'software.index',
                component: () => import('@/views/software/SoftwareIndexView.vue'),
                meta: {
                    title: 'Software',
                    requiresAuth: true,
                }
            },
            {
                path: '/software/:id',
                name: 'software.show',
                component: () => import('@/views/software/SoftwareShowView.vue'),
                meta: {
                    title: 'Detalle de Software',
                    requiresAuth: true,
                }
            },
            {
                path: '/software/create',
                name: 'software.create',
                component: () => import('@/views/software/SoftwareCreateEditView.vue'),
                meta: {
                    title: 'Registrar Software',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },
            {
                path: '/software/:id/edit',
                name: 'software.edit',
                component: () => import('@/views/software/SoftwareCreateEditView.vue'),
                meta: {
                    title: 'Editar Software',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },

            // ─────────────────────────────────────────────────────────────────
            // RESERVAS
            // ─────────────────────────────────────────────────────────────────
            {
                path: '/reservations',
                name: 'reservations.index',
                component: () => import('@/views/reservations/ReservationsIndexView.vue'),
                meta: {
                    title: 'Mis Reservas',
                    requiresAuth: true,
                }
            },
            {
                path: '/reservations/students',
                name: 'reservations.students',
                component: () => import('@/views/reservations/ReservationsByRoleView.vue'),
                meta: {
                    title: 'Reservas de Estudiantes',
                    requiresAuth: true,
                    requiresAdmin: true,
                    reservationRole: 'student',
                }
            },
            {
                path: '/reservations/teachers',
                name: 'reservations.teachers',
                component: () => import('@/views/reservations/ReservationsByRoleView.vue'),
                meta: {
                    title: 'Reservas de Maestros',
                    requiresAuth: true,
                    requiresAdmin: true,
                    reservationRole: 'teacher',
                }
            },
            {
                path: '/reservations/create',
                name: 'reservations.create',
                component: () => import('@/views/reservations/ReservationsCreateView.vue'),
                meta: {
                    title: 'Nueva Reserva',
                    requiresAuth: true,
                }
            },
            {
                path: '/labs/:id/map',
                name: 'labs.map',
                component: () => import('@/views/labs/LabMapView.vue'),
                meta: {
                    title: 'Mapa del Laboratorio',
                    requiresAuth: true,
                }
            },
            {
                path: '/labs/:id/layout',
                name: 'labs.layout',
                component: () => import('@/views/labs/LabLayoutEditorView.vue'),
                meta: {
                    title: 'Plano del Laboratorio',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },
            {
                path: '/incidents',
                name: 'incidents.index',
                component: () => import('@/views/equipment/IncidentsView.vue'),
                meta: {
                    title: 'Incidencias',
                    requiresAuth: true,
                    requiresPermission: 'incidents.viewAny',
                }
            },
            {
                path: '/labs/:id/schedule',
                name: 'labs.schedule',
                component: () => import('@/views/labs/LabScheduleView.vue'),
                meta: {
                    title: 'Horario del Laboratorio',
                    requiresAuth: true,
                    requiresPermission: 'schedule.manage',
                }
            },
            {
                path: '/closures',
                name: 'closures.index',
                component: () => import('@/views/schedule/ClosuresView.vue'),
                meta: {
                    title: 'Cierres',
                    requiresAuth: true,
                    requiresPermission: 'schedule.manage',
                }
            },
            {
                path: '/academic-periods',
                name: 'academic-periods.index',
                component: () => import('@/views/schedule/AcademicPeriodsView.vue'),
                meta: {
                    title: 'Periodos Académicos',
                    requiresAuth: true,
                    requiresPermission: 'schedule.manage',
                }
            },
            {
                path: '/users',
                name: 'users.index',
                component: () => import('@/views/users/UsersIndexView.vue'),
                meta: {
                    title: 'Usuarios',
                    requiresAuth: true,
                    requiresPermission: 'users.viewAny',
                }
            },
            {
                path: '/users/:id',
                name: 'users.show',
                component: () => import('@/views/users/UserDetailView.vue'),
                meta: {
                    title: 'Detalle de Usuario',
                    requiresAuth: true,
                    requiresPermission: 'users.view',
                }
            },
            {
                path: '/reports',
                name: 'reports.index',
                component: () => import('@/views/reports/ReportsView.vue'),
                meta: {
                    title: 'Reportes',
                    requiresAuth: true,
                    requiresPermission: 'reports.view',
                }
            },
            {
                path: '/notifications',
                name: 'notifications.index',
                component: () => import('@/views/NotificationsView.vue'),
                meta: {
                    title: 'Notificaciones',
                    requiresAuth: true,
                }
            },
            {
                path: '/reservations/pending',
                name: 'reservations.pending',
                component: () => import('@/views/reservations/ReservationsPendingView.vue'),
                meta: {
                    title: 'Solicitudes Pendientes',
                    requiresAuth: true,
                    // Por permiso y no por rol: quien tenga reservations.approve
                    // (el administrador, o quien lo reciba por rol individual)
                    // ve la cola.
                    requiresPermission: 'reservations.approve',
                }
            },
            {
                path: '/reservations/:id',
                name: 'reservations.show',
                component: () => import('@/views/reservations/ReservationsShowView.vue'),
                meta: {
                    title: 'Detalle de Reserva',
                    requiresAuth: true,
                }
            },

            // ─────────────────────────────────────────────────────────────────
            // PERFIL DE USUARIO
            // ─────────────────────────────────────────────────────────────────
            {
                path: '/profile',
                name: 'profile',
                component: () => import('@/views/ProfileView.vue'),
                meta: {
                    title: 'Mi Perfil',
                    requiresAuth: true,
                }
            },

            // ─────────────────────────────────────────────────────────────────
            // GESTIÓN DE ROLES Y PERMISOS (Solo administradores)
            // ─────────────────────────────────────────────────────────────────
            {
                path: '/roles',
                name: 'roles.index',
                component: () => import('@/views/roles/RolesIndexView.vue'),
                meta: {
                    title: 'Roles',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },
            {
                path: '/roles/create',
                name: 'roles.create',
                component: () => import('@/views/roles/RolesCreateEditView.vue'),
                meta: {
                    title: 'Crear Rol',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },
            {
                path: '/roles/:id/edit',
                name: 'roles.edit',
                component: () => import('@/views/roles/RolesCreateEditView.vue'),
                meta: {
                    title: 'Editar Rol',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },
            {
                path: '/permissions',
                name: 'permissions.index',
                component: () => import('@/views/roles/PermissionsIndexView.vue'),
                meta: {
                    title: 'Permisos del Sistema',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },
            {
                path: '/group-role-assignments',
                name: 'group-role-assignments.index',
                component: () => import('@/views/roles/GroupRoleAssignmentsView.vue'),
                meta: {
                    title: 'Reglas de Grupo',
                    requiresAuth: true,
                    requiresAdmin: true,
                }
            },
        ]
    },

    // =========================================================================
    // PÁGINA NO ENCONTRADA (404)
    // =========================================================================
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/NotFoundView.vue'),
        meta: {
            title: 'Página no encontrada',
        }
    },
];

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CREAR INSTANCIA DEL ROUTER
 * ═══════════════════════════════════════════════════════════════════════════
 */
const router = createRouter({
    // El prefijo llega de VITE_BASE_PATH: '/' en local, '/lab-reserva/' en el
    // despliegue compartido. Ver utils/basePath.js.
    history: createWebHistory(routerBase),
    routes,

    // Scroll al inicio al cambiar de ruta
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        }
        return { top: 0 };
    },
});

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * NAVIGATION GUARD GLOBAL
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Se ejecuta antes de cada navegación de ruta.
 *
 * RESPONSABILIDADES:
 * 1. Verificar si la ruta requiere autenticación
 * 2. Verificar si la ruta es solo para invitados (guest)
 * 3. Verificar permisos específicos (admin, teacher, etc.)
 * 4. Redirigir al login si no está autenticado
 * 5. Redirigir al dashboard si está autenticado e intenta ir a login/register
 * 6. Actualizar el título de la página
 */
router.beforeEach(async (to, from, next) => {
    // Obtener el store de autenticación
    const authStore = useAuthStore();

    // ─────────────────────────────────────────────────────────────────────────
    // Si es la primera navegación, verificar si hay una sesión activa
    // ─────────────────────────────────────────────────────────────────────────
    if (from.name === undefined && !authStore.user) {
        try {
            await authStore.restoreSession();
        } catch (error) {
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Actualizar título de la página
    // ─────────────────────────────────────────────────────────────────────────
    if (to.meta.title) {
        document.title = `${to.meta.title} | Lab-Reserva`;
    } else {
        document.title = 'Lab-Reserva';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Verificar si la ruta requiere autenticación
    // ─────────────────────────────────────────────────────────────────────────
    if (to.meta.requiresAuth) {
        if (!authStore.isAuthenticated) {
            return next({
                name: 'login',
                query: { redirect: to.fullPath } // Guardar la ruta a la que quería ir
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // Contraseña temporal: la única ruta permitida es la de cambiarla
        // ─────────────────────────────────────────────────────────────────────
        if (authStore.mustChangePassword && to.name !== 'password.change') {
            return next({ name: 'password.change', replace: true });
        }

        if (!authStore.mustChangePassword && to.name === 'password.change') {
            return next({ name: 'dashboard', replace: true });
        }

        // ─────────────────────────────────────────────────────────────────────
        // Verificar si requiere permisos de administrador
        // ─────────────────────────────────────────────────────────────────────
        if (to.meta.requiresAdmin && !authStore.isAdmin) {
            return next({
                name: 'dashboard',
                replace: true
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // Verificar si requiere permisos de profesor
        // ─────────────────────────────────────────────────────────────────────
        if (to.meta.requiresTeacher && !authStore.isTeacher && !authStore.isAdmin) {
            return next({
                name: 'dashboard',
                replace: true
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // Verificar un permiso efectivo concreto (subject.action)
        // ─────────────────────────────────────────────────────────────────────
        if (to.meta.requiresPermission && !authStore.can(to.meta.requiresPermission)) {
            return next({
                name: 'dashboard',
                replace: true
            });
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Verificar si la ruta es solo para invitados (guest)
    // ─────────────────────────────────────────────────────────────────────────
    if (to.meta.guest && authStore.isAuthenticated) {
        return next({ name: 'dashboard', replace: true });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Si todo está bien, continuar con la navegación
    // ─────────────────────────────────────────────────────────────────────────
    next();
});

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * AFTER EACH HOOK
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Se ejecuta después de cada navegación exitosa.
 * Útil para analytics, logging, etc.
 */
router.afterEach((to, from) => {
});

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * HACER EL ROUTER ACCESIBLE GLOBALMENTE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Esto permite que el interceptor de Axios acceda al router
 * para hacer redirecciones cuando sea necesario
 */
if (typeof window !== 'undefined') {
    window.router = router;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * EXPORTAR ROUTER
 * ═══════════════════════════════════════════════════════════════════════════
 */
export default router;
