<template>
  <div class="space-y-6">
    <DetailPanel
      title="Detalle de reserva"
      :subtitle="item ? targetLabel(item) : ''"
      :loading="loading"
      :error="error"
      :fields="fields"
      @retry="load"
    >
      <template #actions>
        <div v-if="item" class="flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium" :class="statusClasses(item)">
            {{ statusLabel(item) }}
          </span>

          <ReservationApprovalActions
            v-if="isPending(item) && authStore.canApproveReservations"
            :reservation="item"
            @approved="load"
            @rejected="load"
          />

          <template v-else>
            <BaseButton v-if="item.can_check_in" :loading="busy === 'check-in'" @click="startCheckIn">
              Registrar llegada
            </BaseButton>
            <BaseButton
              v-if="authStore.canApproveReservations && canMarkNoShow(item)"
              variant="secondary"
              :loading="busy === 'no-show'"
              @click="handleNoShow"
            >
              Marcar inasistencia
            </BaseButton>
            <BaseButton v-if="canCancel" variant="danger" :loading="busy === 'cancel'" @click="handleCancel">
              {{ cancelActionLabel(item) }}
            </BaseButton>
            <BaseButton v-if="canCancel && isInSeries(item)" variant="danger" :loading="busy === 'series'" @click="handleCancelSeries">
              Cancelar toda la serie
            </BaseButton>
          </template>
        </div>
      </template>
    </DetailPanel>

    <div
      v-if="showCheckInNotice"
      class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200"
    >
      <template v-if="isOwner">
        Tu código de check-in es
        <code class="rounded bg-white px-2 py-0.5 font-mono text-base font-bold dark:bg-gray-800">{{ item.check_in_code }}</code>.
        Podrás registrar tu llegada desde {{ formatDateTime(item.check_in_opens_at) }}; si no lo haces dentro del periodo de gracia, la reserva se liberará.
      </template>
      <template v-else>
        Código de check-in del usuario:
        <code class="rounded bg-white px-2 py-0.5 font-mono text-base font-bold dark:bg-gray-800">{{ item.check_in_code }}</code>.
        El check-in se abre a las {{ formatDateTime(item.check_in_opens_at) }}.
      </template>
    </div>

    <CheckInModal v-model="showCheckIn" :loading="busy === 'check-in'" :server-message="checkInError" @confirm="handleCheckIn" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import DetailPanel from '@/components/ui/DetailPanel.vue';
import CheckInModal from '@/components/reservations/CheckInModal.vue';
import ReservationApprovalActions from '@/components/reservations/ReservationApprovalActions.vue';
import { useAuthStore } from '@/stores/auth';
import { useReservations } from '@/composables/useReservations';
import { useResource } from '@/composables/useResource';
import { useToast } from '@/composables/useToast';
import { confirmAction, confirmDestructive } from '@/utils/confirm';
import {
  canMarkNoShow,
  cancelActionLabel,
  isCancellable,
  isInSeries,
  isLabReservation,
  isPending,
  labNameOf,
  statusClasses,
  statusLabel,
  targetLabel,
} from '@/utils/reservationStatus';

const route = useRoute();
const authStore = useAuthStore();
const toast = useToast();

// Se usa useResource para la carga por id: useReservations sigue el dialecto
// antiguo y no la expone.
const { item, loading, error, fetchById } = useResource('reservations', {
  singular: 'reserva',
  plural: 'reservas',
});

const {
  cancelMyReservation, checkIn, markNoShow, cancelSeries, error: actionError,
} = useReservations();

const busy = ref(null);
const showCheckIn = ref(false);
const checkInError = ref(null);

const formatDateTime = (value) => (value
  ? new Intl.DateTimeFormat('es', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(value))
  : null);

const fields = computed(() => (item.value ? [
  { label: 'Tipo', value: isLabReservation(item.value) ? 'Laboratorio completo (clase)' : 'Equipo individual' },
  { label: 'Laboratorio', value: labNameOf(item.value) },
  { label: 'Equipo', value: isLabReservation(item.value) ? 'Todos los equipos del laboratorio' : item.value.equipment?.identifier },
  { label: 'Motivo', value: item.value.purpose },
  { label: 'Inicio', value: formatDateTime(item.value.start_time) },
  { label: 'Fin', value: formatDateTime(item.value.end_time) },
  { label: 'Duración', value: item.value.duration_minutes ? `${item.value.duration_minutes} min` : null },
  { label: 'Solicitante', value: item.value.user?.name },
  { label: 'Revisada por', value: item.value.reviewer?.name },
  { label: 'Fecha de revisión', value: formatDateTime(item.value.reviewed_at) },
  { label: 'Motivo del rechazo', value: item.value.rejection_reason },
  { label: 'Llegada registrada', value: formatDateTime(item.value.checked_in_at) },
  { label: 'Inasistencia registrada', value: formatDateTime(item.value.no_show_at) },
  { label: 'Serie', value: isInSeries(item.value) ? 'Clase semanal recurrente' : null },
] : []));

const isOwner = computed(() => item.value?.user_id === authStore.user?.id);

const showCheckInNotice = computed(() => !!item.value?.check_in_code
  && item.value.status === 'confirmed'
  && !item.value.checked_in_at
  && !item.value.is_past);

// El servidor vuelve a comprobarlo (policy + reglas de negocio); aquí solo
// se decide si mostrar el botón.
const canCancel = computed(() => item.value
  && isCancellable(item.value)
  && (item.value.user_id === authStore.user?.id || authStore.canApproveReservations));

async function load() {
  try {
    await fetchById(route.params.id);
  } catch {
    /* el panel muestra error.value */
  }
}

async function run(kind, action, successMessage) {
  busy.value = kind;

  try {
    const result = await action();

    if (result !== null && result !== false) {
      toast.success(typeof successMessage === 'function' ? successMessage(result) : successMessage);
      await load();

      return true;
    }

    toast.error(actionError.value ?? 'La operación no se pudo completar.');

    return false;
  } finally {
    busy.value = null;
  }
}

async function handleCancel() {
  const pending = isPending(item.value);
  const confirmed = await confirmDestructive({
    title: pending ? '¿Retirar la solicitud?' : '¿Cancelar la reserva?',
    html: `Se ${pending ? 'retirará la solicitud' : 'cancelará la reserva'} de <strong>${targetLabel(item.value)}</strong>.`,
    confirmText: pending ? 'Sí, retirar' : 'Sí, cancelar',
  });

  if (!confirmed) return;

  await run('cancel', () => cancelMyReservation(item.value.id), pending ? 'Solicitud retirada.' : 'Reserva cancelada.');
}

async function handleCancelSeries() {
  const confirmed = await confirmDestructive({
    title: '¿Cancelar toda la serie?',
    html: 'Se cancelarán todas las clases futuras de esta serie semanal. Las ya impartidas no cambian.',
    confirmText: 'Sí, cancelar la serie',
  });

  if (!confirmed) return;

  await run('series', () => cancelSeries(item.value.id), (count) => `Se cancelaron ${count} clases de la serie.`);
}

async function handleNoShow() {
  const confirmed = await confirmAction({
    title: '¿Marcar inasistencia?',
    html: 'La reserva pasará a "No asistió", liberará la franja y contará para la política de reincidencia del usuario.',
    confirmText: 'Sí, marcar',
  });

  if (!confirmed) return;

  await run('no-show', () => markNoShow(item.value.id), 'Inasistencia registrada.');
}

function startCheckIn() {
  checkInError.value = null;

  // Quien ve cualquier reserva no necesita el código (kiosco).
  if (authStore.canApproveReservations && item.value.user_id !== authStore.user?.id) {
    run('check-in', () => checkIn(item.value.id), 'Llegada registrada.');

    return;
  }

  showCheckIn.value = true;
}

async function handleCheckIn(code) {
  const ok = await run('check-in', () => checkIn(item.value.id, code), 'Llegada registrada. ¡Buen trabajo!');

  if (ok) {
    showCheckIn.value = false;
  } else {
    checkInError.value = actionError.value;
  }
}

onMounted(load);
</script>
