import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import apiClient from '@/utils/api';
import {
    clearToken,
    getToken,
    setToken,
    setUnauthorizedHandler,
} from '@/utils/session';

/**
 * Store de autenticación.
 *
 * La aplicación autentica con token Bearer; el token lo guarda y lo lee
 * `utils/session`, que es también quien avisa a este store cuando el servidor
 * rechaza la sesión.
 */
export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);
    const errors = ref({});

    const isAuthenticated = computed(() => !!user.value);
    const userRole = computed(() => user.value?.role ?? null);
    const isAdmin = computed(() => userRole.value === 'admin');
    const isTeacher = computed(() => userRole.value === 'teacher');
    const userName = computed(() => user.value?.name ?? '');

    const userRoleLabel = computed(() => ({
        admin: 'Admin',
        teacher: 'Profesor',
        student: 'Estudiante',
    })[userRole.value] ?? 'Usuario');

    /**
     * Permisos efectivos ("subject.action") que el backend resolvió para el
     * usuario. La interfaz los usa para decidir qué mostrar; la autorización
     * real la sigue haciendo el servidor en cada petición.
     */
    const permissions = computed(() => user.value?.permissions ?? []);

    /**
     * Cuotas y ventanas de reserva del rol del usuario, para validar en
     * cliente antes de enviar.
     */
    const reservationLimits = computed(() => user.value?.reservation_limits ?? null);

    function can(permission) {
        return permissions.value.includes(permission);
    }

    const canCreateLabReservation = computed(() => can('reservations.createLab'));
    const canApproveReservations = computed(() => can('reservations.approve'));

    /**
     * Limpia el estado local. No llama al servidor.
     */
    function resetSession() {
        user.value = null;
        errors.value = {};
        clearToken();
    }

    // Cuando el interceptor recibe un 401 sobre una ruta protegida, el estado
    // local debe caer con él. Sin esto, el guard del router seguía viendo
    // sesión iniciada y rebotaba a una ruta protegida, que daba otro 401.
    setUnauthorizedHandler(() => {
        user.value = null;
        errors.value = {};
    });

    /**
     * Traduce un error de Axios al mapa de errores del formulario.
     */
    function captureError(error, fallback) {
        if (!error.response) {
            // Sin respuesta: red caída o servidor inalcanzable. Antes esto se
            // presentaba igual que unas credenciales incorrectas.
            errors.value = {
                general: ['No se pudo contactar con el servidor. Revisa tu conexión.'],
            };

            return;
        }

        if (error.response.status === 422) {
            errors.value = error.response.data.errors ?? {};

            return;
        }

        if (error.response.status === 429) {
            errors.value = {
                general: ['Demasiados intentos. Espera un minuto y vuelve a probar.'],
            };

            return;
        }

        errors.value = { general: [fallback] };
    }

    async function login(credentials) {
        loading.value = true;
        errors.value = {};

        try {
            const { data } = await apiClient.post('/login', {
                email: credentials.email,
                password: credentials.password,
            });

            if (data.token) {
                setToken(data.token);
            }

            user.value = data.user;

            return user.value;
        } catch (error) {
            resetSession();
            captureError(error, 'No se pudo iniciar sesión. Inténtalo de nuevo.');
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function register(payload) {
        loading.value = true;
        errors.value = {};

        try {
            const { data } = await apiClient.post('/register', payload);

            if (data.token) {
                setToken(data.token);
            }

            user.value = data.user;

            return user.value;
        } catch (error) {
            resetSession();
            captureError(error, 'No se pudo completar el registro. Inténtalo de nuevo.');
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        loading.value = true;

        try {
            if (getToken()) {
                await apiClient.post('/logout');
            }
        } finally {
            // Pase lo que pase en el servidor, la sesión local se cierra: dejar
            // al usuario "dentro" tras pulsar salir es peor que perder la
            // revocación del token.
            resetSession();
            loading.value = false;
        }
    }

    /**
     * Restaura la sesión a partir del token almacenado.
     *
     * @returns {Promise<boolean>} true solo si el token es válido de verdad.
     */
    async function restoreSession() {
        if (!getToken()) {
            user.value = null;

            return false;
        }

        try {
            const { data } = await apiClient.get('/me');
            user.value = data.data ?? data;

            return true;
        } catch {
            // La versión anterior se tragaba el 401 dentro de getUser() y
            // devolvía true igualmente, de modo que un token caducado se
            // quedaba en localStorage para siempre y el guard creía que la
            // sesión estaba restaurada. El interceptor ya lo habrá limpiado;
            // esto lo deja explícito.
            resetSession();

            return false;
        }
    }

    function clearErrors() {
        errors.value = {};
    }

    /**
     * Edita nombre y correo. Los errores 422 los gestiona el formulario.
     */
    async function updateProfile(payload) {
        const { data } = await apiClient.patch('/profile', payload);
        user.value = data.data ?? data;

        return user.value;
    }

    async function changePassword(payload) {
        const { data } = await apiClient.put('/profile/password', payload);

        return data.message;
    }

    async function forgotPassword(email) {
        const { data } = await apiClient.post('/forgot-password', { email });

        return data.message;
    }

    async function resetPassword(payload) {
        const { data } = await apiClient.post('/reset-password', payload);

        return data.message;
    }

    return {
        user,
        loading,
        errors,
        isAuthenticated,
        isAdmin,
        isTeacher,
        userRole,
        userName,
        userRoleLabel,
        permissions,
        reservationLimits,
        can,
        canCreateLabReservation,
        canApproveReservations,
        login,
        register,
        logout,
        restoreSession,
        clearErrors,
        updateProfile,
        changePassword,
        forgotPassword,
        resetPassword,
    };
});
