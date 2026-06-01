import { ref } from 'vue';
import api from '@/utils/api';

export function useUserRoles() {
  const userRoles = ref([]);
  const groupAssignments = ref([]);
  const isLoading = ref(false);
  const error = ref(null);
  const validationErrors = ref({});

  function clearErrors() {
    error.value = null;
    validationErrors.value = {};
  }

  // ── Roles individuales de un usuario ─────────────────────────────────────

  async function fetchUserRoles(userId) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.get(`/users/${userId}/roles`);
      userRoles.value = data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cargar los roles del usuario.';
    } finally {
      isLoading.value = false;
    }
  }

  async function assignUserRole(userId, payload) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.post(`/users/${userId}/roles`, payload);
      return data.data ?? data;
    } catch (err) {
      if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors ?? {};
      } else {
        error.value = err.response?.data?.message ?? 'Error al asignar el rol.';
      }
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  async function revokeUserRole(userId, userRoleId) {
    isLoading.value = true;
    clearErrors();
    try {
      await api.delete(`/users/${userId}/roles/${userRoleId}`);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al revocar el rol.';
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  // ── Reglas de asignación por grupo ───────────────────────────────────────

  async function fetchGroupAssignments(params = {}) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.get('/group-role-assignments', { params });
      groupAssignments.value = data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cargar las reglas de grupo.';
    } finally {
      isLoading.value = false;
    }
  }

  async function createGroupAssignment(payload) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.post('/group-role-assignments', payload);
      return data.data ?? data;
    } catch (err) {
      if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors ?? {};
      } else {
        error.value = err.response?.data?.message ?? 'Error al crear la regla de grupo.';
      }
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  async function toggleGroupAssignment(assignmentId) {
    isLoading.value = true;
    clearErrors();
    try {
      const { data } = await api.patch(`/group-role-assignments/${assignmentId}/toggle`);
      return data.data ?? data;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cambiar el estado de la regla.';
      return null;
    } finally {
      isLoading.value = false;
    }
  }

  async function deleteGroupAssignment(assignmentId) {
    isLoading.value = true;
    clearErrors();
    try {
      await api.delete(`/group-role-assignments/${assignmentId}`);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al eliminar la regla de grupo.';
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  return {
    userRoles,
    groupAssignments,
    isLoading,
    error,
    validationErrors,
    fetchUserRoles,
    assignUserRole,
    revokeUserRole,
    fetchGroupAssignments,
    createGroupAssignment,
    toggleGroupAssignment,
    deleteGroupAssignment,
  };
}
