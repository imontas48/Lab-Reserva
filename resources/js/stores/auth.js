import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient, { resetAuth } from '@/utils/api';

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * STORE DE AUTENTICACIÓN - PINIA
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Gestión centralizada del estado de autenticación de la aplicación.
 *
 * RESPONSABILIDADES:
 * - Almacenar los datos del usuario autenticado
 * - Proveer getters para verificar el estado de autenticación
 * - Manejar las acciones de login, registro y logout
 * - Sincronizar el estado con el backend
 *
 * FLUJO DE AUTENTICACIÓN:
 * 1. Usuario envía credenciales → login()
 * 2. Backend valida y establece cookie de sesión
 * 3. Llamamos a getUser() para obtener los datos del usuario
 * 4. Poblamos el state.user con los datos
 * 5. El getter isAuthenticated devuelve true
 *
 * IMPORTANTE:
 * - Este store NO almacena tokens JWT (Sanctum usa cookies)
 * - Las cookies son HTTP-only y gestionadas automáticamente por el navegador
 * - Solo almacenamos los datos del usuario para mostrar en la UI
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
export const useAuthStore = defineStore('auth', () => {
    // =========================================================================
    // STATE
    // =========================================================================

    /**
     * Usuario autenticado actual
     * null = no autenticado
     * object = usuario autenticado con sus datos
     */
    const user = ref(null);

    /**
     * Errores de validación del último intento de login/registro
     * Se usa para mostrar mensajes de error en los formularios
     */
    const errors = ref({});

    /**
     * Indica si hay una operación en curso (login, registro, logout)
     * Útil para mostrar spinners/loaders en la UI
     */
    const loading = ref(false);

    // =========================================================================
    // GETTERS
    // =========================================================================

    /**
     * Verifica si hay un usuario autenticado
     * @returns {boolean}
     */
    const isAuthenticated = computed(() => !!user.value);

    /**
     * Verifica si el usuario es administrador
     * @returns {boolean}
     */
    const isAdmin = computed(() => {
        return user.value?.role === 'admin' || user.value?.is_admin === true;
    });

    /**
     * Verifica si el usuario es profesor
     * @returns {boolean}
     */
    const isTeacher = computed(() => {
        return user.value?.role === 'teacher' || user.value?.role === 'profesor';
    });

    /**
     * Verifica si el usuario es estudiante
     * @returns {boolean}
     */
    const isStudent = computed(() => {
        return user.value?.role === 'student' || user.value?.role === 'estudiante';
    });

    /**
     * Obtiene el nombre completo del usuario
     * @returns {string}
     */
    const userName = computed(() => {
        if (!user.value) return '';
        return user.value.name || user.value.full_name || user.value.email;
    });

    /**
     * Obtiene el email del usuario
     * @returns {string}
     */
    const userEmail = computed(() => {
        return user.value?.email || '';
    });

    // =========================================================================
    // ACTIONS
    // =========================================================================

    /**
     * -------------------------------------------------------------------------
     * OBTENER USUARIO AUTENTICADO
     * -------------------------------------------------------------------------
     *
     * Obtiene los datos del usuario actualmente autenticado desde el backend.
     *
     * Esta función es crucial porque:
     * - Sanctum usa cookies para la autenticación (no devuelve tokens)
     * - Después de login/registro, necesitamos obtener los datos del usuario
     * - Al recargar la página, necesitamos verificar si hay una sesión activa
     *
     * @returns {Promise<Object|null>} Datos del usuario o null si no está autenticado
     */
    const getUser = async () => {
        try {
            loading.value = true;
            errors.value = {};

            // Llamar al endpoint /api/user (protegido por Sanctum)
            const response = await apiClient.get('/user');

            // Almacenar los datos del usuario
            user.value = response.data.data || response.data;

            console.log('✅ Usuario autenticado:', user.value);

            return user.value;
        } catch (error) {
            console.error('❌ Error al obtener usuario:', error);

            // Si hay error (ej. 401), asegurar que user sea null
            user.value = null;

            // Si es 401, no es realmente un error, solo no está autenticado
            if (error.response?.status === 401) {
                return null;
            }

            throw error;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * LOGIN
     * -------------------------------------------------------------------------
     *
     * Autentica al usuario con email y contraseña.
     *
     * FLUJO:
     * 1. Axios obtiene automáticamente el token CSRF (interceptor)
     * 2. Enviamos las credenciales a /api/login
     * 3. Backend valida y establece cookie de sesión
     * 4. Llamamos a getUser() para obtener los datos del usuario
     * 5. Redirigimos al dashboard (esto se hace en el componente)
     *
     * @param {Object} credentials - { email, password, remember }
     * @returns {Promise<Object>} Datos del usuario autenticado
     */
    const login = async (credentials) => {
        try {
            loading.value = true;
            errors.value = {};

            console.log('🔐 Iniciando sesión...');

            // Llamar al endpoint de login
            // El interceptor de Axios ya obtuvo el token CSRF automáticamente
            await apiClient.post('/v1/login', {
                email: credentials.email,
                password: credentials.password,
                remember: credentials.remember || false,
            });

            console.log('✅ Login exitoso, obteniendo datos del usuario...');

            // Obtener los datos del usuario recién autenticado
            await getUser();

            console.log('✅ Usuario autenticado completamente');

            return user.value;
        } catch (error) {
            console.error('❌ Error en login:', error);

            // Extraer errores de validación si existen
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors || {};
                console.error('Errores de validación:', errors.value);
            } else if (error.response?.status === 401) {
                // Credenciales inválidas
                errors.value = {
                    email: ['Las credenciales proporcionadas son incorrectas.']
                };
            } else {
                // Otro tipo de error
                errors.value = {
                    general: ['Ocurrió un error al iniciar sesión. Por favor, intenta nuevamente.']
                };
            }

            throw error;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * REGISTRO
     * -------------------------------------------------------------------------
     *
     * Registra un nuevo usuario en el sistema.
     *
     * FLUJO:
     * 1. Axios obtiene automáticamente el token CSRF (interceptor)
     * 2. Enviamos los datos del nuevo usuario a /api/register
     * 3. Backend crea el usuario y establece cookie de sesión
     * 4. Llamamos a getUser() para obtener los datos del usuario
     * 5. Redirigimos al dashboard (esto se hace en el componente)
     *
     * @param {Object} data - Datos del nuevo usuario
     * @returns {Promise<Object>} Datos del usuario registrado
     */
    const register = async (data) => {
        try {
            loading.value = true;
            errors.value = {};

            console.log('📝 Registrando nuevo usuario...');

            // Llamar al endpoint de registro
            await apiClient.post('/v1/register', data);

            console.log('✅ Registro exitoso, obteniendo datos del usuario...');

            // Obtener los datos del usuario recién registrado
            await getUser();

            console.log('✅ Usuario registrado completamente');

            return user.value;
        } catch (error) {
            console.error('❌ Error en registro:', error);

            // Extraer errores de validación si existen
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors || {};
                console.error('Errores de validación:', errors.value);
            } else {
                // Otro tipo de error
                errors.value = {
                    general: ['Ocurrió un error al registrar el usuario. Por favor, intenta nuevamente.']
                };
            }

            throw error;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * LOGOUT
     * -------------------------------------------------------------------------
     *
     * Cierra la sesión del usuario actual.
     *
     * FLUJO:
     * 1. Llamamos a /api/logout
     * 2. Backend invalida la sesión y elimina la cookie
     * 3. Reseteamos el estado local (user = null)
     * 4. Reseteamos el estado de autenticación en Axios
     * 5. Redirigimos al login (esto se hace en el componente)
     *
     * @returns {Promise<void>}
     */
    const logout = async () => {
        try {
            loading.value = true;

            console.log('👋 Cerrando sesión...');

            // Llamar al endpoint de logout
            await apiClient.post('/v1/logout');

            console.log('✅ Sesión cerrada en el servidor');
        } catch (error) {
            console.error('❌ Error al cerrar sesión:', error);
            // Aún así, limpiar el estado local
        } finally {
            // Resetear el estado local
            user.value = null;
            errors.value = {};
            loading.value = false;

            // Resetear el estado de autenticación en Axios
            // (esto limpia el flag de CSRF token)
            resetAuth();

            console.log('✅ Estado local limpiado');
        }
    };

    /**
     * -------------------------------------------------------------------------
     * LIMPIAR ERRORES
     * -------------------------------------------------------------------------
     *
     * Limpia los errores de validación almacenados.
     * Útil para limpiar los mensajes de error cuando el usuario
     * empieza a escribir nuevamente en el formulario.
     */
    const clearErrors = () => {
        errors.value = {};
    };

    /**
     * -------------------------------------------------------------------------
     * VERIFICAR AUTENTICACIÓN
     * -------------------------------------------------------------------------
     *
     * Verifica si hay una sesión activa en el backend.
     * Esta función se debe llamar al iniciar la aplicación
     * para restaurar el estado de autenticación.
     *
     * @returns {Promise<boolean>} true si está autenticado, false si no
     */
    const checkAuth = async () => {
        try {
            await getUser();
            return true;
        } catch (error) {
            // Si hay error, el usuario no está autenticado
            return false;
        }
    };

    // =========================================================================
    // RETURN (Composables API)
    // =========================================================================

    return {
        // State
        user,
        errors,
        loading,

        // Getters
        isAuthenticated,
        isAdmin,
        isTeacher,
        isStudent,
        userName,
        userEmail,

        // Actions
        getUser,
        login,
        register,
        logout,
        clearErrors,
        checkAuth,
    };
});
