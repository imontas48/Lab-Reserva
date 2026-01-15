<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mis Reservas</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Gestiona tus reservas de laboratorio</p>
      </div>

      <router-link
        to="/reservations/create"
        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
      >
        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Nueva Reserva
      </router-link>
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
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Cargando tus reservas...</p>
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
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No tienes reservas</h3>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Comienza creando tu primera reserva de equipo</p>
      <router-link
        to="/reservations/create"
        class="mt-4 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700"
      >
        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Crear Reserva
      </router-link>
    </div>

    <!-- Reservations List -->
    <div v-else class="space-y-4">
      <!-- Filters/Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            :class="[
              activeTab === tab.value
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300',
              'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors'
            ]"
          >
            {{ tab.label }}
            <span
              :class="[
                activeTab === tab.value
                  ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                  : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                'ml-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium'
              ]"
            >
              {{ getCountForTab(tab.value) }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Reservations Cards -->
      <div class="grid gap-4 sm:grid-cols-1 lg:grid-cols-2">
        <div
          v-for="reservation in filteredReservations"
          :key="reservation.id"
          class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
        >
          <!-- Status Badge -->
          <div class="mb-4 flex items-start justify-between">
            <span
              :class="{
                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': reservation.is_future,
                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': reservation.is_active,
                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': reservation.is_past,
                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': reservation.status === 'cancelled'
              }"
              class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
            >
              <span
                :class="{
                  'bg-green-400': reservation.is_future,
                  'bg-blue-400': reservation.is_active,
                  'bg-gray-400': reservation.is_past,
                  'bg-red-400': reservation.status === 'cancelled'
                }"
                class="mr-2 h-2 w-2 rounded-full"
              ></span>
              {{ getStatusLabel(reservation) }}
            </span>

            <span class="text-xs text-gray-500 dark:text-gray-400">#{{ reservation.id }}</span>
          </div>

          <!-- Equipment Info -->
          <div class="mb-4">
            <div class="flex items-center text-gray-900 dark:text-white">
              <svg class="mr-2 h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              <span class="font-medium">
                {{ reservation.equipment?.identifier || `Equipo #${reservation.equipment_id}` }}
              </span>
            </div>

            <div v-if="reservation.equipment?.lab" class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">
              <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              {{ reservation.equipment.lab.name }}
            </div>
          </div>

          <!-- Date & Time Info -->
          <div class="space-y-2 border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
              <svg class="mr-2 h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span>{{ formatDate(reservation.start_time) }}</span>
            </div>

            <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
              <svg class="mr-2 h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span>{{ formatTime(reservation.start_time) }} - {{ formatTime(reservation.end_time) }}</span>
              <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ reservation.duration_minutes }} min)</span>
            </div>
          </div>

          <!-- Actions -->
          <div v-if="reservation.is_future && reservation.status === 'confirmed'" class="mt-4 flex justify-end">
            <button
              @click="handleCancelReservation(reservation)"
              :disabled="cancellingId === reservation.id"
              class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 dark:border-red-600 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-900/20 dark:focus:ring-offset-gray-800"
            >
              <svg v-if="cancellingId === reservation.id" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              {{ cancellingId === reservation.id ? 'Cancelando...' : 'Cancelar Reserva' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReservations } from '@/composables/useReservations';
import { useToast } from '@/composables/useToast';

// ============================================================================
// COMPOSABLES
// ============================================================================

const { reservations, loading, error, fetchMyReservations, cancelMyReservation } = useReservations();
const toast = useToast();

// ============================================================================
// STATE
// ============================================================================

const activeTab = ref('upcoming');
const cancellingId = ref(null);

const tabs = [
  { label: 'Próximas', value: 'upcoming' },
  { label: 'Activas', value: 'active' },
  { label: 'Pasadas', value: 'past' },
  { label: 'Canceladas', value: 'cancelled' }
];

// ============================================================================
// COMPUTED
// ============================================================================

const filteredReservations = computed(() => {
  switch (activeTab.value) {
    case 'upcoming':
      return reservations.value.filter(r => r.is_future && r.status === 'confirmed');
    case 'active':
      return reservations.value.filter(r => r.is_active && r.status === 'confirmed');
    case 'past':
      return reservations.value.filter(r => r.is_past && r.status !== 'cancelled');
    case 'cancelled':
      return reservations.value.filter(r => r.status === 'cancelled');
    default:
      return reservations.value;
  }
});

// ============================================================================
// METHODS
// ============================================================================

const getCountForTab = (tabValue) => {
  switch (tabValue) {
    case 'upcoming':
      return reservations.value.filter(r => r.is_future && r.status === 'confirmed').length;
    case 'active':
      return reservations.value.filter(r => r.is_active && r.status === 'confirmed').length;
    case 'past':
      return reservations.value.filter(r => r.is_past && r.status !== 'cancelled').length;
    case 'cancelled':
      return reservations.value.filter(r => r.status === 'cancelled').length;
    default:
      return 0;
  }
};

const getStatusLabel = (reservation) => {
  if (reservation.status === 'cancelled') return 'Cancelada';
  if (reservation.is_active) return 'En Uso Ahora';
  if (reservation.is_future) return 'Programada';
  if (reservation.is_past) return 'Completada';
  return reservation.status;
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const loadReservations = async () => {
  const success = await fetchMyReservations();
  if (success) {
    toast.success(`${reservations.value.length} reservas cargadas`);
  }
};

const handleCancelReservation = async (reservation) => {
  if (!confirm(`¿Estás seguro de cancelar la reserva del ${formatDate(reservation.start_time)}?`)) {
    return;
  }

  cancellingId.value = reservation.id;

  try {
    const success = await cancelMyReservation(reservation.id);

    if (success) {
      toast.success('Reserva cancelada exitosamente');
      // Recargar la lista
      await loadReservations();
    } else {
      toast.error('No se pudo cancelar la reserva');
    }
  } finally {
    cancellingId.value = null;
  }
};

// ============================================================================
// LIFECYCLE
// ============================================================================

onMounted(async () => {
  console.log('🚀 ReservationsIndexView montado');
  await loadReservations();
});
</script>
