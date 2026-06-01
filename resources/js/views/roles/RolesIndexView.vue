<template>
  <div>
    <!-- Encabezado -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Roles</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Gestión de roles y sus permisos asignados
        </p>
      </div>
      <router-link
        to="/roles/create"
        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
      >
        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nuevo Rol
      </router-link>
    </div>

    <!-- DataTable -->
    <DataTable
      :columns="columns"
      :items="roles"
      :loading="isLoading"
      :error="error"
    >
      <!-- Badge display_name + is_system -->
      <template #cell-display_name="{ item }">
        <div class="flex items-center gap-2">
          <span
            :class="colorClasses[item.color] ?? colorClasses.blue"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
          >
            {{ item.display_name }}
          </span>
          <span
            v-if="item.is_system"
            class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
          >
            Sistema
          </span>
        </div>
      </template>

      <!-- Conteo de permisos -->
      <template #cell-permissions_count="{ value }">
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ value ?? '—' }}</span>
      </template>

      <!-- Badge activo / inactivo -->
      <template #cell-is_active="{ value }">
        <span
          :class="value
            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
            : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
          class="inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5"
        >
          {{ value ? 'Activo' : 'Inactivo' }}
        </span>
      </template>

      <!-- Acciones -->
      <template #actions="{ item }">
        <div class="flex items-center justify-end gap-2">
          <button
            @click="goToEdit(item)"
            :disabled="item.is_system"
            :title="item.is_system ? 'Los roles del sistema no pueden editarse' : 'Editar rol'"
            class="rounded p-1 text-green-600 transition-colors hover:bg-green-50 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 dark:text-green-400 dark:hover:bg-green-900/30 disabled:cursor-not-allowed disabled:opacity-40"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            @click="confirmDelete(item)"
            :disabled="item.is_system"
            :title="item.is_system ? 'Los roles del sistema no pueden eliminarse' : 'Eliminar rol'"
            class="rounded p-1 text-red-600 transition-colors hover:bg-red-50 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/30 disabled:cursor-not-allowed disabled:opacity-40"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Modal confirmación de eliminación -->
    <div
      v-if="roleToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="roleToDelete = null"
    >
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Eliminar rol</h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          ¿Estás seguro de que deseas eliminar el rol
          <strong class="text-gray-900 dark:text-white">{{ roleToDelete.display_name }}</strong>?
          Esta acción no se puede deshacer.
        </p>
        <div class="mt-6 flex justify-end gap-3">
          <button
            @click="roleToDelete = null"
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useRoles } from '@/composables/useRoles';
import { useToast } from '@/composables/useToast';
import DataTable from '@/components/ui/DataTable.vue';

const router = useRouter();
const toast = useToast();
const { roles, isLoading, error, fetchRoles, deleteRole } = useRoles();

const roleToDelete = ref(null);

const columns = [
  { key: 'display_name', label: 'Rol' },
  { key: 'name',         label: 'Slug' },
  { key: 'permissions_count', label: 'Permisos' },
  { key: 'is_active',    label: 'Estado' },
];

const colorClasses = {
  blue:   'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  green:  'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
  red:    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
  yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
  purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
  orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
  gray:   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};

function goToEdit(role) {
  router.push({ name: 'roles.edit', params: { id: role.id } });
}

function confirmDelete(role) {
  roleToDelete.value = role;
}

async function handleDelete() {
  const ok = await deleteRole(roleToDelete.value.id);
  if (ok) {
    toast.success('Rol eliminado correctamente.');
    roleToDelete.value = null;
    await fetchRoles();
  } else {
    toast.error(error.value ?? 'No se pudo eliminar el rol.');
  }
}

onMounted(() => fetchRoles());
</script>
