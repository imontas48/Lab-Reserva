import { onUnmounted, ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * Centro de notificaciones del usuario autenticado.
 *
 * No hay websockets: el contador se refresca por sondeo (startPolling), que
 * es suficiente para avisos de aprobación y recordatorios.
 */
export function useNotifications() {
    const notifications = ref([]);
    const meta = ref(null);
    const unreadCount = ref(0);
    const loading = ref(false);
    const error = ref(null);

    let pollTimer = null;

    async function fetchUnreadCount() {
        try {
            const { data } = await apiClient.get('/notifications/unread-count');
            unreadCount.value = data.data?.unread ?? 0;
        } catch {
            // Un fallo del sondeo no debe molestar al usuario.
        }

        return unreadCount.value;
    }

    async function fetchNotifications(params = {}) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.get('/notifications', { params });
            notifications.value = data.data;
            meta.value = data.meta ?? null;

            return notifications.value;
        } catch (err) {
            error.value = err?.response?.data?.message ?? 'No se pudieron cargar las notificaciones.';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function markAsRead(id) {
        const { data } = await apiClient.patch(`/notifications/${id}/read`);
        const index = notifications.value.findIndex((n) => n.id === id);

        if (index !== -1) {
            notifications.value.splice(index, 1, data.data);
        }

        unreadCount.value = Math.max(0, unreadCount.value - 1);

        return data.data;
    }

    async function markAllAsRead() {
        await apiClient.post('/notifications/read-all');
        notifications.value = notifications.value.map((n) => ({ ...n, is_read: true, read_at: n.read_at ?? new Date().toISOString() }));
        unreadCount.value = 0;
    }

    function startPolling(intervalMs = 60000) {
        stopPolling();
        fetchUnreadCount();
        pollTimer = setInterval(fetchUnreadCount, intervalMs);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    onUnmounted(stopPolling);

    return {
        notifications,
        meta,
        unreadCount,
        loading,
        error,
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAllAsRead,
        startPolling,
        stopPolling,
    };
}
