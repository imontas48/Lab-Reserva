<template>
  <div>
    <!-- Encabezado -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Laboratorios</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Gestión de laboratorios de cómputo
        </p>
      </div>
      <router-link
        v-if="authStore.isAdmin"
        to="/labs/create"
        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
      >
        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo Laboratorio
      </router-link>
    </div>

    <!-- DataTable -->
    <DataTable
      :columns="columns"
      :items="labs"
      :loading="loading"
      :error="error"
      @view-item="handleView"
      @edit-item="handleEdit"
      @delete-item="handleDelete"
    >
      <!-- Capacidad con formato -->
      <template #cell-capacity="{ value }">
        <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
          <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          {{ value || 0 }} {{ value === 1 ? 'persona' : 'personas' }}
        </div>
      </template>

      <!-- Descripción truncada -->
      <template #cell-description="{ value }">
        <span
          class="text-sm text-gray-600 dark:text-gray-400"
          :title="value || 'Sin descripción'"
        >
          {{ value ? (value.length > 50 ? value.substring(0, 50) + '...' : value) : 'Sin descripción' }}
        </span>
      </template>

      <!-- Badge de estado activo/inactivo -->
      <template #cell-is_active="{ value }">
        <span
          :class="{
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': value,
            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': !value
          }"
          class="inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5"
        >
          {{ value ? 'Activo' : 'Inactivo' }}
        </span>
      </template>

      <!-- Acciones personalizadas -->
      <template #actions="{ item }">
        <div class="flex items-center justify-end space-x-2">
          <!-- Ver -->
          <button
            @click="handleView(item)"
            class="rounded p-1 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:text-blue-400 dark:hover:bg-blue-900/30 dark:hover:text-blue-300 dark:focus:ring-offset-gray-800"
            title="Ver detalles"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>

          <!-- Editar (solo admin) -->
          <button
            v-if="authStore.isAdmin"
            @click="handleEdit(item)"
            class="rounded p-1 text-green-600 transition-colors hover:bg-green-50 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:text-green-400 dark:hover:bg-green-900/30 dark:hover:text-green-300 dark:focus:ring-offset-gray-800"
            title="Editar laboratorio"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>

          <!-- Eliminar (solo admin) -->
          <button
            v-if="authStore.isAdmin"
            @click="handleDelete(item)"
            class="rounded p-1 text-red-600 transition-colors hover:bg-red-50 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:text-red-400 dark:hover:bg-red-900/30 dark:hover:text-red-300 dark:focus:ring-offset-gray-800"
            title="Eliminar laboratorio"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </template>

      <!-- Estado vacío personalizado -->
      <template #empty>
        <div class="py-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
            No hay laboratorios registrados
          </h3>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Comienza creando tu primer laboratorio.
          </p>
          <router-link
            v-if="authStore.isAdmin"
            to="/labs/create"
            class="mt-4 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
          >
            Crear Laboratorio
          </router-link>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useLabs } from '@/composables/useLabs';
import { useToast } from '@/composables/useToast';
import DataTable from '@/components/ui/DataTable.vue';
import Swal from 'sweetalert2';

// ============================================================================
// COMPOSABLES Y STORES
// ============================================================================

const router = useRouter();
const authStore = useAuthStore();
const { labs, loading, error, fetchLabs, deleteLab } = useLabs();
const toast = useToast();

// ============================================================================
// STATE
// ============================================================================

// ============================================================================
// CONFIGURACIÓN DE LA TABLA
// ============================================================================

const columns = ref([
  {
    key: 'name',
    label: 'Nombre',
    headerClass: 'w-1/4'
  },
  {
    key: 'location',
    label: 'Ubicación',
    headerClass: 'w-1/3'
  },
  {
    key: 'capacity',
    label: 'Capacidad',
    headerClass: 'w-32'
  },
  {
    key: 'description',
    label: 'Descripción',
    headerClass: 'w-1/4'
  },
  {
    key: 'is_active',
    label: 'Estado',
    headerClass: 'w-24'
  }
]);

// ============================================================================
// MÉTODOS
// ============================================================================

/**
 * Maneja el evento de ver un laboratorio
 */
const handleView = (lab) => {
  router.push(`/labs/${lab.id}`);
};

/**
 * Maneja el evento de editar un laboratorio
 */
const handleEdit = (lab) => {
  router.push(`/labs/${lab.id}/edit`);
};

/**
 * Maneja el evento de eliminar un laboratorio
 */
const handleDelete = async (lab) => {
  // Mostrar diálogo de confirmación con SweetAlert2
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    html: `Se eliminará el laboratorio <strong>"${lab.name}"</strong>.<br>Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusCancel: true
  });

  // Si el usuario cancela, no hacer nada
  if (!result.isConfirmed) {
    return;
  }

  try {
    await deleteLab(lab.id);
    console.log(' Laboratorio eliminado');

    // Mostrar notificación de éxito
    toast.success(`Laboratorio "${lab.name}" eliminado exitosamente`);
  } catch (err) {
    console.error(' Error al eliminar laboratorio:', err);

    // Mostrar notificación de error
    toast.error('Error al eliminar el laboratorio. Por favor, intenta nuevamente.');
  }
};

// ============================================================================
// LIFECYCLE
// ============================================================================

onMounted(() => {
  fetchLabs();
});
</script>
