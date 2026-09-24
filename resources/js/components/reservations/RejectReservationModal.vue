<template>
  <BaseModal :model-value="modelValue" title="Rechazar solicitud" size="sm" @update:model-value="close">
    <form id="reject-reservation-form" class="space-y-4" @submit.prevent="submit">
      <p class="text-sm text-gray-600 dark:text-gray-300">
        El solicitante verá este motivo. Sé concreto para que pueda corregir su petición.
      </p>

      <div>
        <label for="reject-reason" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
          Motivo del rechazo <span class="text-red-500">*</span>
        </label>
        <textarea
          id="reject-reason"
          v-model="reason"
          rows="4"
          class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 dark:bg-gray-700 dark:text-white"
          :class="errors.reason
            ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600'"
          :aria-invalid="!!errors.reason"
          aria-describedby="reject-reason-error"
        ></textarea>
        <p v-if="errors.reason" id="reject-reason-error" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.reason }}
        </p>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="loading" @click="close">Cancelar</BaseButton>
      <BaseButton type="submit" form="reject-reservation-form" variant="danger" :loading="loading">
        Rechazar solicitud
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { watch } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import { useValidatedForm } from '@/composables/useValidatedForm';

/**
 * Pide el motivo obligatorio de un rechazo. No llama a la API: emite el
 * motivo y el padre decide (así ReservationApprovalActions es el único sitio
 * que conoce el endpoint).
 */
const props = defineProps({
  modelValue: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  /** Errores del servidor (422) a volcar sobre el campo. */
  serverError: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const schema = yup.object({
  reason: yup
    .string()
    .required('Indica el motivo del rechazo.')
    .min(3, 'El motivo debe tener al menos 3 caracteres.')
    .max(500, 'El motivo no puede superar los 500 caracteres.'),
});

const { defineField, errors, handleSubmit, resetForm, applyServerErrors } = useValidatedForm(schema, { reason: '' });
const [reason] = defineField('reason');

const submit = handleSubmit((values) => emit('confirm', values.reason.trim()));

function close() {
  if (!props.loading) {
    emit('update:modelValue', false);
  }
}

watch(() => props.modelValue, (open) => {
  if (open) resetForm({ values: { reason: '' } });
});

watch(() => props.serverError, (error) => {
  if (error) applyServerErrors(error);
});
</script>
