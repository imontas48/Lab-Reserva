<template>
  <article class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-4 flex items-start justify-between gap-2">
      <div class="flex flex-wrap gap-2">
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium" :class="statusClasses(reservation)">
          {{ statusLabel(reservation) }}
        </span>
        <span
          v-if="isLab"
          class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-800 dark:bg-purple-900/30 dark:text-purple-300"
        >
          Clase
        </span>
        <span
          v-if="isInSeries(reservation)"
          class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300"
        >
          Serie semanal
        </span>
        <span
          v-if="reservation.checked_in_at"
          class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300"
        >
          Llegada registrada
        </span>
      </div>
      <router-link :to="`/reservations/${reservation.id}`" class="text-xs text-gray-500 hover:underline dark:text-gray-400">
        #{{ reservation.id }}
      </router-link>
    </div>

    <div class="mb-4">
      <p class="font-medium text-gray-900 dark:text-white">{{ targetLabel(reservation) }}</p>
      <p v-if="reservation.purpose" class="mt-1 text-sm text-gray-600 dark:text-gray-300">
        {{ reservation.purpose }}
      </p>
      <p
        v-if="reservation.rejection_reason"
        class="mt-2 rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:bg-rose-900/30 dark:text-rose-300"
      >
        Motivo del rechazo: {{ reservation.rejection_reason }}
      </p>
    </div>

    <div class="space-y-1 border-t border-gray-200 pt-4 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
      <p>{{ formatDate(reservation.start_time) }}</p>
      <p>
        {{ formatTime(reservation.start_time) }} – {{ formatTime(reservation.end_time) }}
        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ reservation.duration_minutes }} min)</span>
      </p>
      <p v-if="showCode" class="pt-1 text-xs text-gray-500 dark:text-gray-400">
        Código de check-in:
        <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-sm font-semibold text-gray-900 dark:bg-gray-700 dark:text-white">{{ reservation.check_in_code }}</code>
      </p>
    </div>

    <div v-if="reservation.can_check_in || isCancellable(reservation)" class="mt-4 flex flex-wrap justify-end gap-2">
      <BaseButton v-if="reservation.can_check_in" :loading="checkingIn" @click="$emit('check-in', reservation)">
        Registrar llegada
      </BaseButton>
      <BaseButton v-if="isCancellable(reservation)" variant="danger" :loading="cancelling" @click="$emit('cancel', reservation)">
        {{ cancelActionLabel(reservation) }}
      </BaseButton>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import {
  RESERVATION_STATUS,
  cancelActionLabel,
  isCancellable,
  isInSeries,
  isLabReservation,
  statusClasses,
  statusLabel,
  targetLabel,
} from '@/utils/reservationStatus';

/**
 * Tarjeta de una reserva propia en "Mis Reservas".
 */
const props = defineProps({
  reservation: { type: Object, required: true },
  cancelling: { type: Boolean, default: false },
  checkingIn: { type: Boolean, default: false },
});

defineEmits(['cancel', 'check-in']);

const isLab = computed(() => isLabReservation(props.reservation));

const showCode = computed(() => props.reservation.check_in_code
  && props.reservation.status === RESERVATION_STATUS.CONFIRMED
  && !props.reservation.checked_in_at
  && !props.reservation.is_past);

const formatDate = (value) => (value
  ? new Date(value).toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
  : '');

const formatTime = (value) => (value
  ? new Date(value).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
  : '');
</script>
