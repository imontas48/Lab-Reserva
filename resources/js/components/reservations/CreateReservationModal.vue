<template>
  <BaseModal :model-value="show" :title="title" size="md" :close-on-backdrop="!loading" @update:model-value="close">
    <form id="create-reservation-form" class="space-y-5" @submit.prevent="submit">
      <p class="text-sm text-gray-600 dark:text-gray-300">{{ intro }}</p>

      <dl class="grid grid-cols-1 gap-x-6 gap-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-600 dark:bg-gray-700 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ targetHeading }}</dt>
          <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ targetName }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Inicio</dt>
          <dd class="mt-1 text-gray-900 dark:text-white">{{ formatDateTime(reservationDetails.start) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Fin</dt>
          <dd class="mt-1 text-gray-900 dark:text-white">{{ formatDateTime(reservationDetails.end) }}</dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Duración</dt>
          <dd class="mt-1 text-gray-900 dark:text-white">{{ durationText }}</dd>
        </div>
      </dl>

      <BaseInput
        v-model="purpose"
        name="purpose"
        :label="isLab ? 'Motivo de la clase' : 'Motivo (opcional)'"
        :placeholder="isLab ? 'Ej. Práctica de redes, grupo 3B' : 'Ej. Proyecto final'"
        :error="errors.purpose"
        :required="isLab"
      />

      <RecurrenceFields v-if="isLab" v-model:enabled="recurring" v-model:until="repeatUntil" v-model:weekdays="weekdays" :first-date="reservationDetails.start" :error="errors.repeat_until" />

      <div
        v-if="error && !hasFieldErrors"
        class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300"
        role="alert"
      >
        {{ error }}
      </div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="loading" @click="close">Cancelar</BaseButton>
      <BaseButton type="submit" form="create-reservation-form" :loading="loading">
        {{ confirmLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import RecurrenceFields from './RecurrenceFields.vue';
import { useReservations } from '@/composables/useReservations';
import { useValidatedForm } from '@/composables/useValidatedForm';

/**
 * Confirmación final del asistente: resume la franja, pide el motivo y crea
 * la reserva de equipo, la solicitud de laboratorio o la serie semanal.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  /** 'equipment' | 'lab' */
  mode: { type: String, default: 'equipment', validator: (v) => ['equipment', 'lab'].includes(v) },
  /** { start, end, equipmentId?, labId? } (fechas ISO 8601) */
  reservationDetails: { type: Object, required: true },
  targetName: { type: String, required: true },
  /** Si quien solicita puede aprobar, la clase nace confirmada. */
  autoApproved: { type: Boolean, default: false },
});

const emit = defineEmits(['update:show', 'reservation-success', 'series-success']);

const {
  createReservation, createLabReservation, createRecurringLabReservation, loading, error, validationErrors,
} = useReservations();

const isLab = computed(() => props.mode === 'lab');
const recurring = ref(false);
const repeatUntil = ref('');
const weekdays = ref([]);

const title = computed(() => (isLab.value ? 'Solicitar laboratorio completo' : 'Confirmar reserva'));
const targetHeading = computed(() => (isLab.value ? 'Laboratorio' : 'Equipo'));
const intro = computed(() => (isLab.value
  ? (props.autoApproved
    ? 'Se bloquearán todos los equipos del laboratorio en esta franja.'
    : 'La solicitud quedará pendiente hasta que un administrador la apruebe. Mientras tanto, la franja queda reservada.')
  : 'Verifica los detalles antes de confirmar.'));
const confirmLabel = computed(() => {
  if (isLab.value && recurring.value) return props.autoApproved ? 'Crear serie de clases' : 'Solicitar serie de clases';

  return isLab.value && !props.autoApproved ? 'Enviar solicitud' : 'Confirmar reserva';
});

const schema = computed(() => yup.object({
  purpose: isLab.value
    ? yup.string().required('Indica el motivo de la clase.').min(3, 'El motivo debe tener al menos 3 caracteres.').max(255, 'Máximo 255 caracteres.')
    : yup.string().max(255, 'Máximo 255 caracteres.'),
  repeat_until: yup.string().when([], {
    is: () => isLab.value && recurring.value,
    then: (s) => s.required('Indica hasta qué fecha se repite la clase.'),
    otherwise: (s) => s.notRequired(),
  }),
}));

const { defineField, errors, handleSubmit, resetForm, setFieldValue, applyServerErrors } = useValidatedForm(schema, { purpose: '', repeat_until: '' });
const [purpose] = defineField('purpose');

watch(repeatUntil, (value) => setFieldValue('repeat_until', value));

const hasFieldErrors = computed(() => Object.keys(errors.value).length > 0);

const durationText = computed(() => {
  const minutes = Math.floor((new Date(props.reservationDetails.end) - new Date(props.reservationDetails.start)) / 60000);
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;

  if (hours === 0) return `${rest} minutos`;
  if (rest === 0) return `${hours} ${hours === 1 ? 'hora' : 'horas'}`;

  return `${hours} ${hours === 1 ? 'hora' : 'horas'} y ${rest} minutos`;
});

const formatDateTime = (value) => new Intl.DateTimeFormat('es-ES', {
  dateStyle: 'long', timeStyle: 'short',
}).format(new Date(value));

const submit = handleSubmit(async (values) => {
  const window = {
    start_time: props.reservationDetails.start,
    end_time: props.reservationDetails.end,
  };

  if (isLab.value && recurring.value) {
    const series = await createRecurringLabReservation({
      lab_id: props.reservationDetails.labId,
      purpose: values.purpose,
      repeat_until: repeatUntil.value,
      ...(weekdays.value.length > 0 ? { weekdays: weekdays.value } : {}),
      ...window,
    });

    if (series) {
      emit('series-success', series);
      close();

      return;
    }
  } else {
    const reservation = isLab.value
      ? await createLabReservation({ lab_id: props.reservationDetails.labId, purpose: values.purpose, ...window })
      : await createReservation({
        equipment_id: props.reservationDetails.equipmentId,
        purpose: values.purpose || null,
        ...window,
      });

    if (reservation) {
      emit('reservation-success', reservation);
      close();

      return;
    }
  }

  // Los errores de campo (422) se vuelcan sobre el formulario; el resto se
  // muestra en el aviso general.
  applyServerErrors({ response: { data: { errors: validationErrors.value } } });
});

function close() {
  if (!loading.value) {
    emit('update:show', false);
  }
}

watch(() => props.show, (open) => {
  if (open) {
    resetForm({ values: { purpose: '', repeat_until: '' } });
    recurring.value = false;
    repeatUntil.value = '';
    weekdays.value = [];
    validationErrors.value = {};
    error.value = null;
  }
});
</script>
