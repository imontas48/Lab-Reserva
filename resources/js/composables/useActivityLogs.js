import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * Registro de actividades (solo administradores). Solo lectura: la API no
 * ofrece ninguna forma de editar ni borrar un registro.
 */
export function useActivityLogs() {
    const logs = ref([]);
    const meta = ref(null);
    const loading = ref(false);
    const error = ref(null);

    async function fetchActivityLogs(params = {}) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.get('/activity-logs', { params });
            logs.value = data.data;
            meta.value = data.meta ?? null;

            return logs.value;
        } catch (err) {
            error.value = err?.response?.data?.message ?? 'No se pudo cargar el registro de actividades.';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return { logs, meta, loading, error, fetchActivityLogs };
}
