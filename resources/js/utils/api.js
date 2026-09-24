import axios from 'axios';
import { clearToken, getToken, terminateSession } from './session';

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CLIENTE HTTP
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Autenticación por token Bearer contra una API sin estado.
 *
 * La versión anterior mezclaba dos mecanismos: pedía la cookie CSRF de Sanctum
 * y enviaba `withCredentials`, y además mandaba el token Bearer. Nada decidía
 * cuál era el bueno, y la negociación del CSRF añadía una petición extra antes
 * de cada mutación. Como todas las rutas de la API están tras `auth:sanctum`,
 * que acepta el Bearer, se conserva solo ese camino.
 *
 * El token se añade en cada petición leyéndolo del almacenamiento, en vez de
 * fijarlo una vez en `defaults.headers`: así una sesión cerrada en otra pestaña
 * deja de enviarse de inmediato, en lugar de quedar pegada a la instancia.
 */

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

apiClient.interceptors.request.use(
    (config) => {
        const token = getToken();

        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        } else {
            delete config.headers.Authorization;
        }

        return config;
    },
    (error) => Promise.reject(error)
);

apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        // 401: la sesión ya no vale. Se limpia ANTES de cualquier redirección;
        // si no, el guard del router sigue creyendo que hay sesión y rebota a
        // una ruta protegida, que vuelve a dar 401.
        //
        // El login y el registro se excluyen: ahí un 401 significa
        // "credenciales incorrectas", no "sesión caducada".
        if (status === 401) {
            const url = error.config?.url ?? '';
            const isAuthAttempt = url.includes('/login') || url.includes('/register');

            if (!isAuthAttempt) {
                terminateSession();
            } else {
                clearToken();
            }
        }

        // 403 con código propio: el servidor exige cambiar la contraseña
        // temporal. Puede pasar si la sesión se restauró desde otra pestaña
        // con datos viejos; se lleva al usuario a la pantalla de cambio.
        if (status === 403 && error.response?.data?.code === 'password_change_required') {
            window.router?.push({ name: 'password.change' });
        }

        return Promise.reject(error);
    }
);

export default apiClient;
