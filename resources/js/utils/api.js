import axios from 'axios';

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONFIGURACIÓN DE AXIOS PARA LARAVEL SANCTUM
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Cliente HTTP configurado específicamente para trabajar con Laravel Sanctum.
 *
 * CARACTERÍSTICAS CRÍTICAS:
 *
 * 1. withCredentials: true
 *    - Permite que Axios envíe y reciba cookies HTTP-only automáticamente
 *    - Laravel Sanctum usa cookies para almacenar el token de sesión
 *    - Sin esto, la autenticación basada en cookies NO funcionará
 *
 * 2. CSRF Protection
 *    - Laravel Sanctum requiere un token CSRF para prevenir ataques CSRF
 *    - Antes de cualquier petición POST/PUT/PATCH/DELETE, debemos obtener
 *      el token CSRF llamando a /sanctum/csrf-cookie
 *    - Laravel almacena el token en una cookie XSRF-TOKEN
 *    - Axios lee automáticamente esta cookie y la envía como header X-XSRF-TOKEN
 *
 * 3. Interceptores
 *    - Request: Asegura que el token CSRF esté disponible antes de mutaciones
 *    - Response: Maneja errores de autenticación de forma centralizada
 *
 * FLUJO DE AUTENTICACIÓN:
 * 1. Frontend → GET /sanctum/csrf-cookie (obtiene token CSRF)
 * 2. Frontend → POST /login (con credenciales + token CSRF)
 * 3. Backend establece cookie de sesión (HTTP-only, Secure, SameSite)
 * 4. Todas las peticiones subsecuentes incluyen automáticamente la cookie
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Variable para rastrear si ya tenemos el token CSRF
let csrfTokenReady = false;
let csrfTokenPromise = null;

/**
 * Instancia de Axios configurada para Laravel Sanctum
 */
const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://lab-reserva.test/api/v1',

    // CRÍTICO: Permite enviar y recibir cookies
    // Sin esto, Sanctum no puede establecer ni leer la cookie de sesión
    withCredentials: true,

    // Headers estándar para APIs JSON
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest', // Laravel usa esto para detectar peticiones AJAX
    },
});

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * INTERCEPTOR DE PETICIONES: PROTECCIÓN CSRF
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Antes de cada petición que modifique datos (POST, PUT, PATCH, DELETE),
 * nos aseguramos de tener el token CSRF.
 *
 * ¿Por qué es necesario?
 * - Laravel Sanctum requiere un token CSRF para prevenir ataques CSRF
 * - El token se obtiene llamando a /sanctum/csrf-cookie
 * - Laravel almacena el token en una cookie llamada XSRF-TOKEN
 * - Axios lee automáticamente esta cookie y la envía como header X-XSRF-TOKEN
 *
 * ¿Cuándo se ejecuta?
 * - Solo en peticiones de mutación (POST, PUT, PATCH, DELETE)
 * - Se omite para GET, HEAD, OPTIONS
 * - Se omite para las rutas de login, register y csrf-cookie (para evitar bucles)
 */
apiClient.interceptors.request.use(
    async (config) => {
        const method = config.method?.toUpperCase();
        const url = config.url || '';

        // Solo necesitamos CSRF token para operaciones de mutación
        const needsCsrfToken = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

        // Rutas que no requieren CSRF token previo (para evitar bucles infinitos)
        const excludedRoutes = [
            '/sanctum/csrf-cookie',
            '/login',
            '/register',
        ];

        const isExcludedRoute = excludedRoutes.some(route => url.includes(route));

        // Si la petición necesita CSRF y no está excluida, asegurar que tenemos el token
        if (needsCsrfToken && !isExcludedRoute && !csrfTokenReady) {
            // Si no hay una petición de CSRF en curso, iniciarla
            if (!csrfTokenPromise) {
                csrfTokenPromise = axios.get(
                    `${import.meta.env.VITE_APP_URL || 'http://lab-reserva.test'}/sanctum/csrf-cookie`,
                    { withCredentials: true }
                ).then(() => {
                    csrfTokenReady = true;
                    csrfTokenPromise = null;
                }).catch((error) => {
                    csrfTokenPromise = null;
                    console.error(' Error al obtener token CSRF:', error);
                    throw error;
                });
            }

            // Esperar a que el token esté listo
            await csrfTokenPromise;
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * INTERCEPTOR DE RESPUESTAS: MANEJO GLOBAL DE ERRORES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Maneja errores de forma centralizada para toda la aplicación.
 *
 * CÓDIGOS DE ESTADO IMPORTANTES:
 * - 401 Unauthorized: Usuario no autenticado → Redirigir a login
 * - 419 Page Expired: Token CSRF expirado → Renovar y reintentar
 * - 403 Forbidden: Usuario autenticado pero sin permisos
 * - 422 Unprocessable Entity: Errores de validación
 * - 500 Internal Server Error: Error del servidor
 */
apiClient.interceptors.response.use(
    (response) => {
        // Respuesta exitosa, retornarla tal cual
        return response;
    },
    async (error) => {
        const { response, config } = error;

        if (response) {
            const status = response.status;
            const url = config?.url || '';

            switch (status) {
                case 401:
                    // Usuario no autenticado
                    console.error(' Error 401: No autenticado');

                    // Si no estamos ya en la página de login, redirigir
                    if (!window.location.pathname.includes('/login')) {

                        // Resetear el estado de CSRF
                        csrfTokenReady = false;

                        // Redirigir usando el router de Vue si está disponible
                        // Si no, usar window.location
                        if (window.router) {
                            window.router.push('/login');
                        } else {
                            window.location.href = '/login';
                        }
                    }
                    break;

                case 419:
                    // Token CSRF expirado (Page Expired)
                    console.warn('️ Token CSRF expirado, renovando...');

                    // Resetear el estado de CSRF para forzar renovación
                    csrfTokenReady = false;
                    csrfTokenPromise = null;

                    // Si no estamos en una ruta excluida, reintentar la petición
                    const excludedForRetry = ['/login', '/register'];
                    const shouldRetry = !excludedForRetry.some(route => url.includes(route));

                    if (shouldRetry && !config._retry) {
                        config._retry = true;
                        return apiClient.request(config);
                    }

                    // Si ya lo intentamos una vez, redirigir a login
                    if (window.router) {
                        window.router.push('/login');
                    } else {
                        window.location.href = '/login';
                    }
                    break;

                case 403:
                    // Usuario autenticado pero sin permisos
                    console.error(' Error 403: Acción no autorizada');
                    console.error('No tienes permisos para realizar esta acción');
                    break;

                case 404:
                    // Recurso no encontrado
                    console.error(' Error 404: Recurso no encontrado');
                    break;

                case 422:
                    // Error de validación (Laravel devuelve errores en este código)
                    console.error(' Error 422: Error de validación', response.data);
                    // Los errores de validación se manejan típicamente en los componentes
                    break;

                case 500:
                case 502:
                case 503:
                case 504:
                    // Errores del servidor
                    console.error(` Error ${status}: Error del servidor`);
                    console.error('El servidor encontró un error. Por favor, intenta nuevamente más tarde.');
                    break;

                default:
                    console.error(` Error ${status}: Error en la petición`, error.message);
            }
        } else if (error.request) {
            // La petición se hizo pero no hubo respuesta
            console.error(' Error de red: Sin respuesta del servidor');
            console.error('No se pudo conectar con el servidor. Verifica tu conexión a internet.');
        } else {
            // Error al configurar la petición
            console.error(' Error:', error.message);
        }

        // Siempre rechazar la promesa para que el componente pueda manejar el error
        return Promise.reject(error);
    }
);

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * FUNCIÓN AUXILIAR: RESETEAR AUTENTICACIÓN
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Útil cuando el usuario cierra sesión o cuando detectamos que la sesión expiró.
 */
export const resetAuth = () => {
    csrfTokenReady = false;
    csrfTokenPromise = null;
};

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * EXPORTACIÓN DEFAULT
 * ═══════════════════════════════════════════════════════════════════════════
 */
export default apiClient;
