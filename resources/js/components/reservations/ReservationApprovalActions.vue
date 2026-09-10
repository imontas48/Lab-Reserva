<template>
  <div class="inline-flex items-center gap-2">
    <BaseButton variant="primary" :loading="approving" :disabled="rejecting" @click="approve">
      Aprobar
    </BaseButton>
    <BaseButton variant="danger" :disabled="approving" @click="showRejectModal = true">
      Rechazar
    </BaseButton>

    <RejectReservationModal
      v-model="showRejectModal"
      :loading="rejecting"
      :server-error="rejectServerError"
      @confirm="reject"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import RejectReservationModal from './RejectReservationModal.vue';
import { useReservations } from '@/composables/useReservations';
import { useToast } from '@/composables/useToast';
import { confirmAction } from '@/utils/confirm';
import { targetLabel } from '@/utils/reservationStatus';

/**
 * Botones "Aprobar" y "Rechazar" de una solicitud pendiente.
 *
 * Único componente que conoce los endpoints de aprobación: las vistas que lo
 * usan (cola de pendientes, listados por rol, detalle) solo reaccionan a los
 * eventos para refrescar su propia lista.
 */
const props = defineProps({
  reservation: { type: Object, required: true },
});

const emit = defineEmits(['approved', 'rejected']);

const { approveReservation, rejectReservation, error, validationErrors } = useReservations();
const toast = useToast();

const approving = ref(false);
const rejecting = ref(false);
const showRejectModal = ref(false);
const rejectServerError = ref(null);

async function approve() {
  const confirmed = await confirmAction({
    title: '¿Aprobar la solicitud?',
    html: `Se confirmará la reserva de <strong>${targetLabel(props.reservation)}</strong> y quedarán bloqueados todos sus equipos en esa franja.`,
    confirmText: 'Sí, aprobar',
  });

  if (!confirmed) return;

  approving.value = true;

  try {
    const approved = await approveReservation(props.reservation.id);

    if (approved) {
      toast.success('Solicitud aprobada.');
      emit('approved', approved);
    } else {
      toast.error(error.value ?? 'No se pudo aprobar la solicitud.');
    }
  } finally {
    approving.value = false;
  }
}

async function reject(reason) {
  rejecting.value = true;
  rejectServerError.value = null;

  try {
    const rejected = await rejectReservation(props.reservation.id, reason);

    if (rejected) {
      toast.success('Solicitud rechazada.');
      showRejectModal.value = false;
      emit('rejected', rejected);
    } else if (Object.keys(validationErrors.value).length > 0) {
      // Misma forma que devuelve Laravel, para que el modal la vuelque al campo.
      rejectServerError.value = { response: { data: { errors: validationErrors.value } } };
    } else {
      toast.error(error.value ?? 'No se pudo rechazar la solicitud.');
    }
  } finally {
    rejecting.value = false;
  }
}
</script>
