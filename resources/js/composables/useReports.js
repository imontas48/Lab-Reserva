import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * Reportes de uso (requiere reports.view).
 */
export function useReports() {
    const summary = ref(null);
    const occupancy = ref(null);
    const loading = ref(false);
    const exporting = ref(false);
    const error = ref(null);

    async function fetchSummary(params) {
        const { data } = await apiClient.get('/reports/summary', { params });
        summary.value = data.data;

        return summary.value;
    }

    async function fetchOccupancy(params) {
        const { data } = await apiClient.get('/reports/occupancy', { params });
        occupancy.value = data.data;

        return occupancy.value;
    }

    /**
     * Carga resumen y ocupación contra el mismo rango, para que las cifras
     * siempre concuerden.
     */
    async function fetchAll(params) {
        loading.value = true;
        error.value = null;

        try {
            await Promise.all([fetchSummary(params), fetchOccupancy(params)]);
        } catch (err) {
            error.value = err?.response?.data?.message ?? 'No se pudieron cargar los reportes.';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Descarga el CSV. La API exige el token Bearer, así que no sirve un
     * enlace directo: se pide como blob y se entrega al navegador.
     */
    async function exportCsv(params) {
        exporting.value = true;

        try {
            const response = await apiClient.get('/reports/export', { params, responseType: 'blob' });
            const url = URL.createObjectURL(response.data);
            const link = document.createElement('a');
            link.href = url;
            link.download = `reservas_${params.from}_${params.to}.csv`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        } finally {
            exporting.value = false;
        }
    }

    return {
        summary, occupancy, loading, exporting, error, fetchAll, exportCsv,
    };
}
