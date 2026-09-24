import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * Incidencias de equipos. Contrato de useResource: todo error se lanza.
 */
export function useIncidents() {
    const incidents = ref([]);
    const meta = ref(null);
    const loading = ref(false);
    const error = ref(null);

    async function fetchIncidents(params = {}) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.get('/incidents', { params });
            incidents.value = data.data;
            meta.value = data.meta ?? null;

            return incidents.value;
        } catch (err) {
            error.value = err?.response?.data?.message ?? 'No se pudieron cargar las incidencias.';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchForEquipment(equipmentId) {
        const { data } = await apiClient.get(`/equipment/${equipmentId}/incidents`);
        incidents.value = data.data;

        return incidents.value;
    }

    async function reportIncident(equipmentId, payload) {
        const { data } = await apiClient.post(`/equipment/${equipmentId}/incidents`, payload);
        incidents.value = [data.data, ...incidents.value];

        return data.data;
    }

    async function updateIncident(id, payload) {
        const { data } = await apiClient.patch(`/incidents/${id}`, payload);
        const index = incidents.value.findIndex((i) => i.id === id);

        if (index !== -1) {
            incidents.value.splice(index, 1, data.data);
        }

        return data.data;
    }

    return {
        incidents, meta, loading, error, fetchIncidents, fetchForEquipment, reportIncident, updateIncident,
    };
}
