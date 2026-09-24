<template>
  <BaseModal :model-value="modelValue" title="Registrar llegada" size="sm" @update:model-value="close">
    <form id="check-in-form" class="space-y-4" @submit.prevent="submit">
      <p class="text-sm text-gray-600 dark:text-gray-300">
        Escribe el código de tu reserva para confirmar que has llegado. Lo encuentras en el detalle de la reserva y en el correo de confirmación.
      </p>
      <BaseInput
        v-model="code"
        name="code"
        label="Código de check-in"
        placeholder="Ej. A7K2QX"
        autocomplete="off"
        :error="errors.code"
        required
      />
      <p v-if="serverMessage" class="text-sm text-red-600 dark:text-red-400" role="alert">{{ serverMessage }}</p>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="loading" @click="close">Cancelar</BaseButton>
      <BaseButton type="submit" form="check-in-form" :loading="loading">Confirmar llegada</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { watch } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import { useValidatedForm } from '@/composables/useValidatedForm';

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  serverMessage: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const schema = yup.object({
  code: yup.string().required('Escribe el código.').length(6, 'El código tiene 6 caracteres.'),
});

const { defineField, errors, handleSubmit, resetForm } = useValidatedForm(schema, { code: '' });
const [code] = defineField('code');

const submit = handleSubmit((values) => emit('confirm', values.code.trim().toUpperCase()));

function close() {
  if (!props.loading) emit('update:modelValue', false);
}

watch(() => props.modelValue, (open) => {
  if (open) resetForm({ values: { code: '' } });
});
</script>
