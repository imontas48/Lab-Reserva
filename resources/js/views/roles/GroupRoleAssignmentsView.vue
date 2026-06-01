<template>
  <div>
    <!-- Encabezado -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Reglas de Grupo</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Asignación automática de roles según el tipo de usuario
        </p>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
      >
        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nueva Regla
      </button>
    </div>

    <!-- DataTable -->
    <DataTable
      :columns="columns"
      :items="groupAssignments"
      :loading="isLoading"
      :error="error"
    >
      <!-- Rol asignado -->
      <template #cell-role="{ item }">
        <span class="text-sm font-medium text-gray-900 dark:text-white">
          {{ item.role?.display_name ?? '—' }}
        </span>
      </template>

      <!-- Tipo de grupo -->
      <template #cell-group_type="{ value }">
        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
          {{ value }}
        </span>
      </template>

      <!-- Badge activo/inactivo -->
      <template #cell-is_active="{ value }">
        <span
          :class="value
            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
            : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
          class="inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5"
        >
          {{ value ? 'Activa' : 'Inactiva' }}
        </span>
      </template>

      <!-- Acciones -->
      <template #actions="{ item }">
        <div class="flex items-center justify-end gap-2">
          <!-- Toggle activo/inactivo -->
          <button
            @click="handleToggle(item)"
            :title="item.is_active ? 'Desactivar regla' : 'Activar regla'"
            class="rounded p-1 text-yellow-600 transition-colors hover:bg-yellow-50 hover:text-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 dark:text-yellow-400 dark:hover:bg-yellow-900/30"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
            </svg>
          </button>
          <!-- Eliminar -->
          <button
            @click="confirmDelete(item)"
            title="Eliminar regla"
            class="rounded p-1 text-red-600 transition-colors hover:bg-red-50 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/30"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Modal: Crear Regla -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="closeCreateModal"
    >
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Nueva Regla de Grupo</h3>

        <form @submit.prevent="handleCreate" class="space-y-4">
          <BaseSelect
            v-model="newForm.role_id"
            label="Rol a asignar"
            :options="roleOptions"
            placeholder="Selecciona un rol…"
            :error="firstError('role_id')"
            required
          />

          <BaseSelect
            v-model="newForm.group_type"
            label="Tipo de grupo"
            :options="groupTypeOptions"
            :error="firstError('group_type')"
            required
          />

          <BaseSelect
            v-model="newForm.group_value"
            label="Valor del grupo"
            :options="groupValueOptions"
            :error="firstError('group_value')"
            required
          />

          <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
            {{ error }}
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="closeCreateModal"
              class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="isLoading"
              class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60"
            >
              {{ isLoading ? 'Guardando…' : 'Crear regla' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal confirmación eliminación -->
    <div
      v-if="assignmentToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="assignmentToDelete = null"
    >
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Eliminar regla</h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          ¿Eliminar la regla
          <strong class="text-gray-900 dark:text-white">{{ assignmentToDelete.group_type }}={{ assignmentToDelete.group_value }}</strong>
          → <strong class="text-gray-900 dark:text-white">{{ assignmentToDelete.role?.display_name }}</strong>?
        </p>
        <div class="mt-6 flex justify-end gap-3">
          <button
            @click="assignmentToDelete = null"
            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Cancelar
          </button>
          <button
            @click="handleDelete"
            :disabled="isLoading"
            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60"
          >
            {{ isLoading ? 'Eliminando…' : 'Eliminar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useUserRoles } from '@/composables/useUserRoles';
import { useRoles } from '@/composables/useRoles';
import { useToast } from '@/composables/useToast';
import DataTable from '@/components/ui/DataTable.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';

const toast = useToast();
const { groupAssignments, isLoading, error, validationErrors, fetchGroupAssignments, createGroupAssignment, toggleGroupAssignment, deleteGroupAssignment } = useUserRoles();
const { roles, fetchRoles } = useRoles();

const showCreateModal = ref(false);
const assignmentToDelete = ref(null);

const newForm = ref({ role_id: '', group_type: 'user_type', group_value: '' });

const columns = [
  { key: 'role',        label: 'Rol' },
  { key: 'group_type',  label: 'Tipo de grupo' },
  { key: 'group_value', label: 'Valor' },
  { key: 'is_active',   label: 'Estado' },
];

const roleOptions = computed(() =>
  roles.value.map(r => ({ value: String(r.id), text: r.display_name }))
);

const groupTypeOptions = [
  { value: 'user_type', text: 'Tipo de usuario' },
];

const groupValueOptions = [
  { value: 'admin',    text: 'Administrador' },
  { value: 'teacher',  text: 'Profesor'      },
  { value: 'student',  text: 'Estudiante'    },
];

function firstError(field) {
  return validationErrors.value[field]?.[0] ?? null;
}

function closeCreateModal() {
  showCreateModal.value = false;
  newForm.value = { role_id: '', group_type: 'user_type', group_value: '' };
}

function confirmDelete(item) {
  assignmentToDelete.value = item;
}

async function handleToggle(item) {
  const updated = await toggleGroupAssignment(item.id);
  if (updated) {
    toast.success(`Regla ${updated.is_active ? 'activada' : 'desactivada'}.`);
    await fetchGroupAssignments();
  } else {
    toast.error(error.value ?? 'Error al cambiar el estado.');
  }
}

async function handleCreate() {
  const created = await createGroupAssignment({
    role_id:     Number(newForm.value.role_id),
    group_type:  newForm.value.group_type,
    group_value: newForm.value.group_value,
    is_active:   true,
  });
  if (created) {
    toast.success('Regla creada correctamente.');
    closeCreateModal();
    await fetchGroupAssignments();
  } else {
    toast.error(error.value ?? 'Por favor, corrige los errores.');
  }
}

async function handleDelete() {
  const ok = await deleteGroupAssignment(assignmentToDelete.value.id);
  if (ok) {
    toast.success('Regla eliminada correctamente.');
    assignmentToDelete.value = null;
    await fetchGroupAssignments();
  } else {
    toast.error(error.value ?? 'Error al eliminar la regla.');
  }
}

onMounted(async () => {
  await Promise.all([fetchGroupAssignments(), fetchRoles()]);
});
</script>
