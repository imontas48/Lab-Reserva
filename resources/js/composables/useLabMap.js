import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * Mapa y plano de un laboratorio.
 */
export function useLabMap() {
    const lab = ref(null);
    const equipment = ref([]);
    const generatedAt = ref(null);
    const loading = ref(false);
    const error = ref(null);

    async function fetchMap(labId) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.get(`/labs/${labId}/map`);
            lab.value = data.data.lab;
            equipment.value = data.data.equipment;
            generatedAt.value = data.data.generated_at;

            return data.data;
        } catch (err) {
            error.value = err?.response?.data?.message ?? 'No se pudo cargar el mapa del laboratorio.';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    /**
     * @param {number} labId
     * @param {{ grid_rows: number, grid_cols: number, positions: { equipment_id: number, row: number, col: number }[] }} payload
     */
    async function saveLayout(labId, payload) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.put(`/labs/${labId}/layout`, payload);
            lab.value = data.data.lab;
            equipment.value = data.data.equipment;

            return data.data;
        } catch (err) {
            error.value = err?.response?.data?.message ?? 'No se pudo guardar el plano.';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        lab, equipment, generatedAt, loading, error, fetchMap, saveLayout,
    };
}
