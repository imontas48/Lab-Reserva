import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * Horario de apertura y cierres de laboratorio.
 *
 * Contrato de useResource: todo error se lanza; el llamante decide cómo
 * mostrarlo. `error` y `validationErrors` quedan rellenos para las vistas.
 */
export function useLabSchedule() {
    const schedule = ref(null);
    const closures = ref([]);
    const closuresMeta = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const validationErrors = ref({});

    function capture(err, fallback) {
        validationErrors.value = err?.response?.status === 422 ? (err.response.data.errors ?? {}) : {};
        error.value = err?.response?.data?.message ?? fallback;
        throw err;
    }

    async function fetchSchedule(labId) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.get(`/labs/${labId}/schedule`);
            schedule.value = data.data;

            return schedule.value;
        } catch (err) {
            return capture(err, 'No se pudo cargar el horario del laboratorio.');
        } finally {
            loading.value = false;
        }
    }

    /**
     * @param {number} labId
     * @param {{ weekday: number, opens_at: string, closes_at: string }[]} hours
     */
    async function saveOpeningHours(labId, hours) {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            const { data } = await apiClient.put(`/labs/${labId}/opening-hours`, { hours });

            if (schedule.value) {
                schedule.value = { ...schedule.value, opening_hours: data.data };
            }

            return data.data;
        } catch (err) {
            return capture(err, 'No se pudo guardar el horario.');
        } finally {
            loading.value = false;
        }
    }

    async function fetchClosures(params = {}) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.get('/closures', { params });
            closures.value = data.data;
            closuresMeta.value = data.meta ?? null;

            return closures.value;
        } catch (err) {
            return capture(err, 'No se pudieron cargar los cierres.');
        } finally {
            loading.value = false;
        }
    }

    async function createClosure(payload) {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            const { data } = await apiClient.post('/closures', payload);
            closures.value = [...closures.value, data.data].sort((a, b) => a.starts_at.localeCompare(b.starts_at));

            return data.data;
        } catch (err) {
            return capture(err, 'No se pudo crear el cierre.');
        } finally {
            loading.value = false;
        }
    }

    async function deleteClosure(id) {
        loading.value = true;
        error.value = null;

        try {
            await apiClient.delete(`/closures/${id}`);
            closures.value = closures.value.filter((closure) => closure.id !== id);
        } catch (err) {
            capture(err, 'No se pudo eliminar el cierre.');
        } finally {
            loading.value = false;
        }
    }

    return {
        schedule,
        closures,
        closuresMeta,
        loading,
        error,
        validationErrors,
        fetchSchedule,
        saveOpeningHours,
        fetchClosures,
        createClosure,
        deleteClosure,
    };
}
