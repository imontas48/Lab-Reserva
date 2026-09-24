<template>
  <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="submit">
    <BaseSelect
      v-if="labOptions.length > 0"
      v-model="labId"
      name="lab_id"
      label="Laboratorio"
      placeholder="Todos los laboratorios"
      :options="labOptions"
      :error="errors.lab_id"
    />
    <BaseInput
      v-model="reason"
      name="reason"
      label="Motivo"
      placeholder="Ej. Día festivo, mantenimiento"
      :error="errors.reason"
      required
      :class="{ 'sm:col-span-2': labOptions.length === 0 }"
    />
    <BaseInput v-model="startsAt" name="starts_at" type="datetime-local" label="Desde" :error="errors.starts_at" required />
    <BaseInput v-model="endsAt" name="ends_at" type="datetime-local" label="Hasta" :error="errors.ends_at" required />

    <div class="flex justify-end sm:col-span-2">
      <BaseButton type="submit" :loading="saving">Añadir cierre</BaseButton>
    </div>
  </form>
</template>

<script setup>
import { watch } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import { useValidatedForm } from '@/composables/useValidatedForm';
import { toApiDateTime } from '@/utils/datetime';

/**
 * Alta de un cierre puntual. Si se pasan laboratorios, permite elegir uno o
 * dejarlo global; si no, el padre fija el laboratorio.
 */
const props = defineProps({
  /** [{ value, text }] — vacío cuando el cierre siempre es de un laboratorio concreto */
  labOptions: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
  serverError: { type: Object, default: null },
});

const emit = defineEmits(['submit']);

const schema = yup.object({
  lab_id: yup.string().nullable(),
  reason: yup.string().required('Indica el motivo del cierre.').min(3, 'Mínimo 3 caracteres.').max(255),
  starts_at: yup.string().required('Indica el inicio.'),
  ends_at: yup.string().required('Indica el fin.')
    .test('after', 'El fin debe ser posterior al inicio.', (value, ctx) => !value || !ctx.parent.starts_at || value > ctx.parent.starts_at),
});

const { defineField, errors, handleSubmit, resetForm, applyServerErrors } = useValidatedForm(schema, {
  lab_id: '', reason: '', starts_at: '', ends_at: '',
});

const [labId] = defineField('lab_id');
const [reason] = defineField('reason');
const [startsAt] = defineField('starts_at');
const [endsAt] = defineField('ends_at');

const submit = handleSubmit((values) => {
  emit('submit', {
    lab_id: values.lab_id ? Number(values.lab_id) : null,
    reason: values.reason.trim(),
    starts_at: toApiDateTime(new Date(values.starts_at)),
    ends_at: toApiDateTime(new Date(values.ends_at)),
  });
});

watch(() => props.serverError, (error) => {
  if (error) applyServerErrors(error);
});

defineExpose({ reset: () => resetForm({ values: { lab_id: '', reason: '', starts_at: '', ends_at: '' } }) });
</script>
