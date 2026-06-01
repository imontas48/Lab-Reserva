import { ref } from 'vue';
import api from '@/utils/api';

export function useRoles() {
  const roles = ref([]);
  const role = ref(null);
  const isLoading = ref(false);
  const error = ref(null);
  const validationErrors = ref({});

  function clearErrors() {
    error.value = null;
    validationErrors.value = {};
  }

  async function fetchRoles(params = {}) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.get('/roles', { params });
      roles.value = data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cargar los roles.';
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchRole(id) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.get(`/roles/${id}`);
      role.value = data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cargar el rol.';
    } finally {
      isLoading.value = false;
    }
  }

  async function createRole(payload) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.post('/roles', payload);
      return data.data ?? data;
    } catch (err) {
      if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors ?? {};
      } else {
        error.value = err.response?.data?.message ?? 'Error al crear el rol.';
      }
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  async function updateRole(id, payload) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.put(`/roles/${id}`, payload);
      return data.data ?? data;
    } catch (err) {
      if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors ?? {};
      } else {
        error.value = err.response?.data?.message ?? 'Error al actualizar el rol.';
      }
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  async function deleteRole(id) {
    isLoading.value = true;
    clearErrors();
    try {
      await api.delete(`/roles/${id}`);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al eliminar el rol.';
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  async function syncPermissions(roleId, permissionIds) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.post(`/roles/${roleId}/permissions`, { permission_ids: permissionIds });
      return data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al sincronizar permisos.';
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  return {
    roles,
    role,
    isLoading,
    error,
    validationErrors,
    fetchRoles,
    fetchRole,
    createRole,
    updateRole,
    deleteRole,
    syncPermissions,
  };
}
