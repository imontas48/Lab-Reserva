<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reservas de Maestros</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          Todas las reservas realizadas por maestros
        </p>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="flex flex-wrap gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Buscar por nombre, email o equipo..."
        class="flex-1 min-w-48 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
        @input="debouncedFetch"
      />

      <select
        v-model="statusFilter"
        class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        @change="loadReservations"
      >
        <option value="">Todos los estados</option>
        <option value="confirmed">Confirmadas</option>
        <option value="cancelled">Canceladas</option>
        <option value="completed">Completadas</option>
      </select>

      <button
        @click="loadReservations"
        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
      >
        <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        Buscar
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && reservations.length === 0" class="flex items-center justify-center py-12">
      <div class="text-center">
        <svg
          class="mx-auto h-12 w-12 animate-spin text-blue-600"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Cargando reservas...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
      <div class="flex">
        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Error al cargar reservas</h3>
          <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ error }}</p>
          <button
            @click="loadReservations"
            class="mt-2 text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300"
          >
            Intentar nuevamente
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="reservations.length === 0" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
      <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Sin reservas</h3>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No se encontraron reservas de maestros con los filtros aplicados.</p>
    </div>

    <!-- Reservations Table -->
    <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Maestro</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Equipo / Laboratorio</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Inicio</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Fin</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Estado</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr
            v-for="reservation in reservations"
            :key="reservation.id"
            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
          >
            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
              #{{ reservation.id }}
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ reservation.user?.name ?? '—' }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ reservation.user?.email ?? '' }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ reservation.equipment?.identifier ?? `Equipo #${reservation.equipment_id}` }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ reservation.equipment?.lab?.name ?? '—' }}
              </div>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
              {{ formatDateTime(reservation.start_time) }}
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
              {{ formatDateTime(reservation.end_time) }}
            </td>
            <td class="whitespace-nowrap px-6 py-4">
              <span
                :class="{
                  'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': reservation.status === 'confirmed' && reservation.is_future,
                  'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': reservation.status === 'confirmed' && reservation.is_active,
                  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': reservation.status === 'confirmed' && reservation.is_past,
                  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': reservation.status === 'cancelled',
                }"
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              >
                {{ statusLabel(reservation) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="paginationMeta && paginationMeta.last_page > 1"
      class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
    >
      <p class="text-sm text-gray-700 dark:text-gray-300">
        Mostrando
        <span class="font-medium">{{ paginationMeta.from }}</span>
        –
        <span class="font-medium">{{ paginationMeta.to }}</span>
        de
        <span class="font-medium">{{ paginationMeta.total }}</span>
        reservas
      </p>
      <div class="flex items-center gap-1">
        <button
          :disabled="currentPage <= 1"
          class="rounded px-2 py-1 text-sm text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:bg-gray-700"
          @click="goToPage(currentPage - 1)"
        >
          ‹ Anterior
        </button>
        <button
          v-for="page in visiblePages"
          :key="page"
          :class="[
            'rounded px-3 py-1 text-sm font-medium',
            page === currentPage
              ? 'bg-blue-600 text-white'
              : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700',
          ]"
          @click="goToPage(page)"
        >
          {{ page }}
        </button>
        <button
          :disabled="currentPage >= paginationMeta.last_page"
          class="rounded px-2 py-1 text-sm text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:bg-gray-700"
          @click="goToPage(currentPage + 1)"
        >
          Siguiente ›
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReservations } from '@/composables/useReservations';

const { reservations, loading, error, paginationMeta, fetchReservationsByRole } = useReservations();

const searchQuery = ref('');
const statusFilter = ref('');
const currentPage = ref(1);

let debounceTimer = null;

const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    currentPage.value = 1;
    loadReservations();
  }, 400);
};

const loadReservations = async () => {
  const params = { page: currentPage.value };
  if (searchQuery.value) params.search = searchQuery.value;
  if (statusFilter.value) params.status = statusFilter.value;

  await fetchReservationsByRole('teacher', params);
};

const goToPage = (page) => {
  currentPage.value = page;
  loadReservations();
};

const visiblePages = computed(() => {
  if (!paginationMeta.value) return [];
  const last = paginationMeta.value.last_page;
  const current = currentPage.value;
  const pages = [];
  const start = Math.max(1, current - 2);
  const end = Math.min(last, current + 2);
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

const formatDateTime = (dateString) => {
  if (!dateString) return '—';
  return new Intl.DateTimeFormat('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(dateString));
};

const statusLabel = (reservation) => {
  if (reservation.status === 'cancelled') return 'Cancelada';
  if (reservation.is_active) return 'En Uso';
  if (reservation.is_future) return 'Programada';
  if (reservation.is_past) return 'Completada';
  return reservation.status;
};

onMounted(() => loadReservations());
</script>
