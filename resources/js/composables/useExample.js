import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * Composable de ejemplo para demostrar el patrón
 * 
 * Los composables encapsulan lógica reutilizable y reactiva.
 * Este ejemplo demuestra:
 * - Manejo de estado local con ref()
 * - Operaciones asíncronas con async/await
 * - Manejo de errores
 * - Uso del cliente API centralizado
 */
export function useExample() {
    const data = ref(null);
    const loading = ref(false);
    const error = ref(null);

    /**
     * Obtener datos de ejemplo de la API
     */
    const fetchData = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiClient.get('/example');
            data.value = response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al obtener los datos';
            console.error('Error en fetchData:', err);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Crear un nuevo recurso
     */
    const createResource = async (payload) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiClient.post('/example', payload);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al crear el recurso';
            console.error('Error en createResource:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        // State
        data,
        loading,
        error,
        // Methods
        fetchData,
        createResource,
    };
}
