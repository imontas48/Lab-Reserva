<template>
  <BaseModal :model-value="modelValue" title="Reportar incidencia" size="sm" @update:model-value="close">
    <form id="incident-report-form" class="space-y-4" @submit.prevent="submit">
      <p class="text-sm text-gray-600 dark:text-gray-300">
        Describe el problema del equipo <strong>{{ equipmentName }}</strong>. El administrador lo revisará.
      </p>
      <BaseSelect v-model="severity" name="severity" label="Gravedad" :options="SEVERITIES" :error="errors.severity" />
      <div>
        <label for="incident-description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
          Descripción <span class="text-red-500">*</span>
        </label>
        <textarea
          id="incident-description"
          v-model="description"
          rows="4"
          class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 dark:bg-gray-700 dark:text-white"
          :class="errors.description ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600'"
          placeholder="Ej. El teclado no responde en varias teclas."
        ></textarea>
        <p v-if="errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.description }}</p>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="loading" @click="close">Cancelar</BaseButton>
      <BaseButton type="submit" form="incident-report-form" :loading="loading">Enviar reporte</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { watch } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import { useValidatedForm } from '@/composables/useValidatedForm';

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  equipmentName: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  serverError: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const SEVERITIES = [
  { value: 'low', text: 'Baja: molesta pero se puede usar' },
  { value: 'medium', text: 'Media: limita el uso' },
  { value: 'high', text: 'Alta: el equipo no sirve' },
];

const schema = yup.object({
  severity: yup.string().oneOf(['low', 'medium', 'high']).required(),
  description: yup.string().required('Describe el problema.').min(10, 'Al menos 10 caracteres.').max(2000),
});

const { defineField, errors, handleSubmit, resetForm, applyServerErrors } = useValidatedForm(schema, { severity: 'medium', description: '' });
const [severity] = defineField('severity');
const [description] = defineField('description');

const submit = handleSubmit((values) => emit('submit', { severity: values.severity, description: values.description.trim() }));

function close() {
  if (!props.loading) emit('update:modelValue', false);
}

watch(() => props.modelValue, (open) => {
  if (open) resetForm({ values: { severity: 'medium', description: '' } });
});

watch(() => props.serverError, (err) => {
  if (err) applyServerErrors(err);
});
</script>
