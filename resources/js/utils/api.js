import axios from 'axios';

/**
 * Configuración centralizada de Axios para todas las peticiones API
 * 
 * Esta instancia incluye:
 * - URL base de la API
 * - Headers comunes (CSRF, JSON)
 * - Interceptores para manejo de errores
 * - Soporte para credenciales (cookies)
 */
const apiClient = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

/**
 * Interceptor de respuesta para manejo global de errores
 */
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        // Manejo centralizado de errores HTTP
        if (error.response) {
            switch (error.response.status) {
                case 401:
                    // No autenticado - redirigir a login si es necesario
                    console.error('No autenticado');
                    // Aquí puedes agregar lógica para redirigir al login
                    break;
                case 403:
                    // No autorizado
                    console.error('Acción no autorizada');
                    break;
                case 404:
                    // Recurso no encontrado
                    console.error('Recurso no encontrado');
                    break;
                case 422:
                    // Error de validación - Laravel devuelve errores en este código
                    console.error('Error de validación:', error.response.data);
                    break;
                case 500:
                    // Error del servidor
                    console.error('Error del servidor');
                    break;
                default:
                    console.error('Error en la petición:', error.message);
            }
        } else if (error.request) {
            // La petición se hizo pero no hubo respuesta
            console.error('Sin respuesta del servidor');
        } else {
            // Error al configurar la petición
            console.error('Error:', error.message);
        }

        return Promise.reject(error);
    }
);

export default apiClient;
