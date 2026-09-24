<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mis Reservas</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Gestiona tus reservas de equipos y tus solicitudes de laboratorio</p>
      </div>

      <router-link
        to="/reservations/create"
        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
      >
        + Nueva reserva
      </router-link>
    </div>

    <div v-if="loading && reservations.length === 0" class="flex flex-col items-center justify-center py-12">
      <BaseSpinner size="lg" class="text-blue-600" label="Cargando reservas" />
      <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Cargando tus reservas...</p>
    </div>

    <div
      v-else-if="error"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
      role="alert"
    >
      {{ error }}
      <button class="ml-2 font-medium underline" @click="loadReservations">Intentar nuevamente</button>
    </div>

    <div v-else-if="reservations.length === 0" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">No tienes reservas</h3>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Comienza creando tu primera reserva.</p>
      <router-link
        to="/reservations/create"
        class="mt-4 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700"
      >
        Crear reserva
      </router-link>
    </div>

    <div v-else class="space-y-4">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex flex-wrap gap-x-6" aria-label="Filtrar reservas">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            :class="[
              activeTab === tab.value
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300',
              'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors'
            ]"
            @click="activeTab = tab.value"
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
              {{ counts[tab.value] }}
            </span>
          </button>
        </nav>
      </div>

      <p v-if="filteredReservations.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        No hay reservas en esta categoría.
      </p>

      <div v-else class="grid gap-4 sm:grid-cols-1 lg:grid-cols-2">
        <ReservationCard
          v-for="reservation in filteredReservations"
          :key="reservation.id"
          :reservation="reservation"
          :cancelling="cancellingId === reservation.id"
          :checking-in="checkingInId === reservation.id"
          @cancel="handleCancel"
          @check-in="openCheckIn"
        />
      </div>
    </div>

    <CheckInModal
      v-model="showCheckIn"
      :loading="checkingInId !== null"
      :server-message="checkInError"
      @confirm="handleCheckIn"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import CheckInModal from '@/components/reservations/CheckInModal.vue';
import ReservationCard from '@/components/reservations/ReservationCard.vue';
import { useReservations } from '@/composables/useReservations';
import { useToast } from '@/composables/useToast';
import { confirmDestructive } from '@/utils/confirm';
import { RESERVATION_STATUS, isPending } from '@/utils/reservationStatus';

const {
  reservations, loading, error, fetchMyReservations, cancelMyReservation, checkIn,
} = useReservations();
const toast = useToast();

const activeTab = ref('upcoming');
const cancellingId = ref(null);
const checkingInId = ref(null);
const showCheckIn = ref(false);
const checkInError = ref(null);
const checkInTarget = ref(null);

const tabs = [
  { label: 'Próximas', value: 'upcoming' },
  { label: 'Pendientes', value: 'pending' },
  { label: 'Activas', value: 'active' },
  { label: 'Pasadas', value: 'past' },
  { label: 'Canceladas', value: 'cancelled' },
  { label: 'Rechazadas', value: 'rejected' },
  { label: 'Inasistencias', value: 'no_show' },
];

const FILTERS = {
  upcoming: (r) => r.status === RESERVATION_STATUS.CONFIRMED && r.is_future,
  pending: (r) => isPending(r),
  active: (r) => r.status === RESERVATION_STATUS.CONFIRMED && r.is_active,
  past: (r) => r.status === RESERVATION_STATUS.COMPLETED || (r.status === RESERVATION_STATUS.CONFIRMED && r.is_past),
  cancelled: (r) => r.status === RESERVATION_STATUS.CANCELLED,
  rejected: (r) => [RESERVATION_STATUS.REJECTED, RESERVATION_STATUS.EXPIRED].includes(r.status),
  no_show: (r) => r.status === RESERVATION_STATUS.NO_SHOW,
};

function openCheckIn(reservation) {
  checkInTarget.value = reservation;
  checkInError.value = null;
  showCheckIn.value = true;
}

async function handleCheckIn(code) {
  if (!checkInTarget.value) return;

  checkingInId.value = checkInTarget.value.id;
  checkInError.value = null;

  try {
    const updated = await checkIn(checkInTarget.value.id, code);

    if (updated) {
      toast.success('Llegada registrada. ¡Buen trabajo!');
      showCheckIn.value = false;
    } else {
      checkInError.value = error.value ?? 'No se pudo registrar la llegada.';
    }
  } finally {
    checkingInId.value = null;
  }
}

const filteredReservations = computed(() => reservations.value.filter(FILTERS[activeTab.value] ?? (() => true)));

const counts = computed(() => Object.fromEntries(
  tabs.map((tab) => [tab.value, reservations.value.filter(FILTERS[tab.value]).length]),
));

const loadReservations = () => fetchMyReservations();

async function handleCancel(reservation) {
  const pending = isPending(reservation);
  const dateLabel = new Date(reservation.start_time).toLocaleDateString('es-ES', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
  });

  const confirmed = await confirmDestructive({
    title: pending ? '¿Retirar la solicitud?' : '¿Cancelar la reserva?',
    html: pending
      ? `Se retirará la solicitud de laboratorio del <strong>${dateLabel}</strong>.`
      : `Se cancelará la reserva del <strong>${dateLabel}</strong>.`,
    confirmText: pending ? 'Sí, retirar' : 'Sí, cancelar',
  });

  if (!confirmed) return;

  cancellingId.value = reservation.id;

  try {
    const success = await cancelMyReservation(reservation.id);

    if (success) {
      toast.success(pending ? 'Solicitud retirada.' : 'Reserva cancelada exitosamente.');
      await loadReservations();
    } else {
      toast.error(error.value ?? 'No se pudo cancelar la reserva.');
    }
  } finally {
    cancellingId.value = null;
  }
}

onMounted(loadReservations);
</script>
