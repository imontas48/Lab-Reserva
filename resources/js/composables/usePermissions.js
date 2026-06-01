import { ref } from 'vue';
import api from '@/utils/api';

export function usePermissions() {
  const permissions = ref([]);
  const isLoading = ref(false);
  const error = ref(null);
  const validationErrors = ref({});

  function clearErrors() {
    error.value = null;
    validationErrors.value = {};
  }

  async function fetchPermissions(params = {}) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.get('/permissions', { params });
      permissions.value = data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cargar los permisos.';
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchEffectivePermissions(userId) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.get(`/users/${userId}/effective-permissions`);
      return data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cargar los permisos efectivos.';
      return [];
    } finally {
      isLoading.value = false;
    }
  }

  // ── Sobreescrituras (overrides) ──────────────────────────────────────────

  async function fetchOverrides(userId) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.get(`/users/${userId}/permission-overrides`);
      return data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cargar las sobreescrituras.';
      return [];
    } finally {
      isLoading.value = false;
    }
  }

  async function createOverride(userId, payload) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.post(`/users/${userId}/permission-overrides`, payload);
      return data.data ?? data;
    } catch (err) {
      if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors ?? {};
      } else {
        error.value = err.response?.data?.message ?? 'Error al crear la sobreescritura.';
      }
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  async function deleteOverride(userId, overrideId) {
    isLoading.value = true;
    clearErrors();
    try {
      await api.delete(`/users/${userId}/permission-overrides/${overrideId}`);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al eliminar la sobreescritura.';
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  return {
    permissions,
    isLoading,
    error,
    validationErrors,
    fetchPermissions,
    fetchEffectivePermissions,
    fetchOverrides,
    createOverride,
    deleteOverride,
  };
}
