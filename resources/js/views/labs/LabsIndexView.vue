<template>
  <div>
    <!-- Encabezado -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Laboratorios</h1>
        <p class="mt-2 text-sm text-gray-600">
          Gestión de laboratorios de cómputo
        </p>
      </div>
      <router-link
        v-if="authStore.isAdmin"
        to="/labs/create"
        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
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
      <!-- Badge de estado -->
      <template #cell-status="{ value }">
        <span
          :class="{
            'bg-green-100 text-green-800': value === 'available',
            'bg-yellow-100 text-yellow-800': value === 'maintenance',
            'bg-red-100 text-red-800': value === 'occupied'
          }"
          class="inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5"
        >
          {{ statusLabels[value] || value }}
        </span>
      </template>

      <!-- Capacidad con icono -->
      <template #cell-capacity="{ value }">
        <div class="flex items-center text-sm text-gray-900">
          <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          {{ value }} personas
        </div>
      </template>

      <!-- Equipos con contador -->
      <template #cell-equipment_count="{ value }">
        <span class="text-sm text-gray-900">
          {{ value || 0 }} {{ value === 1 ? 'equipo' : 'equipos' }}
        </span>
      </template>

      <!-- Acciones personalizadas -->
      <template #actions="{ item }">
        <div class="flex items-center justify-end space-x-2">
          <!-- Ver -->
          <button
            @click="handleView(item)"
            class="rounded p-1 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
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
            class="rounded p-1 text-green-600 transition-colors hover:bg-green-50 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
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
            class="rounded p-1 text-red-600 transition-colors hover:bg-red-50 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
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
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          <h3 class="mt-4 text-lg font-medium text-gray-900">
            No hay laboratorios registrados
          </h3>
          <p class="mt-2 text-sm text-gray-500">
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
import DataTable from '@/components/ui/DataTable.vue';
import apiClient from '@/utils/api';

// ============================================================================
// COMPOSABLES Y STORES
// ============================================================================

const router = useRouter();
const authStore = useAuthStore();

// ============================================================================
// STATE
// ============================================================================

const labs = ref([]);
const loading = ref(false);
const error = ref(null);

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
    key: 'building',
    label: 'Edificio',
    headerClass: 'w-1/6'
  },
  {
    key: 'floor',
    label: 'Piso',
    headerClass: 'w-24'
  },
  {
    key: 'capacity',
    label: 'Capacidad',
    headerClass: 'w-32'
  },
  {
    key: 'equipment_count',
    label: 'Equipos',
    headerClass: 'w-32'
  },
  {
    key: 'status',
    label: 'Estado',
    headerClass: 'w-32'
  }
]);

const statusLabels = {
  available: 'Disponible',
  maintenance: 'Mantenimiento',
  occupied: 'Ocupado'
};

// ============================================================================
// MÉTODOS
// ============================================================================

/**
 * Carga la lista de laboratorios desde la API
 */
const loadLabs = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get('/labs');
    labs.value = response.data.data || response.data;
    console.log('✅ Laboratorios cargados:', labs.value.length);
  } catch (err) {
    console.error('❌ Error al cargar laboratorios:', err);
    error.value = 'Error al cargar los laboratorios. Por favor, intenta nuevamente.';
  } finally {
    loading.value = false;
  }
};

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
  if (!confirm(`¿Estás seguro de que deseas eliminar el laboratorio "${lab.name}"?`)) {
    return;
  }

  try {
    await apiClient.delete(`/labs/${lab.id}`);
    console.log('✅ Laboratorio eliminado');

    // Recargar la lista
    await loadLabs();

    // TODO: Mostrar notificación de éxito
  } catch (err) {
    console.error('❌ Error al eliminar laboratorio:', err);
    // TODO: Mostrar notificación de error
    alert('Error al eliminar el laboratorio. Por favor, intenta nuevamente.');
  }
};

// ============================================================================
// LIFECYCLE
// ============================================================================

onMounted(() => {
  loadLabs();
});
</script>
