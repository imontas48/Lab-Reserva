<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Periodos académicos</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-400">
        Semestres o cuatrimestres. Acotan las reservas recurrentes de los profesores.
      </p>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Nuevo periodo</h2>
      <form class="grid grid-cols-1 gap-4 sm:grid-cols-3" @submit.prevent="submit">
        <BaseInput v-model="name" name="name" label="Nombre" placeholder="Ej. 2026-2" :error="errors.name" required />
        <BaseInput v-model="startsOn" name="starts_on" type="date" label="Inicio" :error="errors.starts_on" required />
        <BaseInput v-model="endsOn" name="ends_on" type="date" label="Fin" :error="errors.ends_on" required />
        <div class="flex justify-end sm:col-span-3">
          <BaseButton type="submit" :loading="loading">Crear periodo</BaseButton>
        </div>
      </form>
    </section>

    <DataTable :columns="columns" :items="periods" :loading="loading" :error="error" item-key="id">
      <template #cell-is_current="{ item }">
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="item.is_current
            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
            : (item.is_active ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400')"
        >
          {{ item.is_current ? 'En curso' : (item.is_active ? 'Activo' : 'Inactivo') }}
        </span>
      </template>
      <template #actions="{ item }">
        <div class="inline-flex gap-2">
          <BaseButton variant="secondary" @click="toggle(item)">{{ item.is_active ? 'Desactivar' : 'Activar' }}</BaseButton>
          <BaseButton variant="danger" @click="remove(item)">Eliminar</BaseButton>
        </div>
      </template>
      <template #empty>
        <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Aún no hay periodos académicos.</p>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import DataTable from '@/components/ui/DataTable.vue';
import { useAcademicPeriods } from '@/composables/useAcademicPeriods';
import { useToast } from '@/composables/useToast';
import { useValidatedForm } from '@/composables/useValidatedForm';
import { confirmDestructive } from '@/utils/confirm';

const toast = useToast();
const { periods, loading, error, fetchPeriods, createPeriod, updatePeriod, deletePeriod } = useAcademicPeriods();

const columns = [
  { key: 'name', label: 'Nombre' },
  { key: 'starts_on', label: 'Inicio' },
  { key: 'ends_on', label: 'Fin' },
  { key: 'is_current', label: 'Estado' },
];

const schema = yup.object({
  name: yup.string().required('El nombre es obligatorio.').max(255),
  starts_on: yup.string().required('Indica el inicio.'),
  ends_on: yup.string().required('Indica el fin.')
    .test('after', 'El fin no puede ser anterior al inicio.', (value, ctx) => !value || !ctx.parent.starts_on || value >= ctx.parent.starts_on),
});

const { defineField, errors, handleSubmit, resetForm, applyServerErrors } = useValidatedForm(schema, { name: '', starts_on: '', ends_on: '' });
const [name] = defineField('name');
const [startsOn] = defineField('starts_on');
const [endsOn] = defineField('ends_on');

const submit = handleSubmit(async (values) => {
  try {
    await createPeriod(values);
    resetForm({ values: { name: '', starts_on: '', ends_on: '' } });
    toast.success('Periodo creado.');
    await fetchPeriods();
  } catch (err) {
    if (!applyServerErrors(err)) toast.error('No se pudo crear el periodo.');
  }
});

async function toggle(period) {
  try {
    await updatePeriod(period.id, { is_active: !period.is_active });
    await fetchPeriods();
  } catch {
    toast.error('No se pudo actualizar el periodo.');
  }
}

async function remove(period) {
  const confirmed = await confirmDestructive({ title: '¿Eliminar el periodo?', html: `Se eliminará <strong>${period.name}</strong>.` });
  if (!confirmed) return;

  try {
    await deletePeriod(period.id);
    toast.success('Periodo eliminado.');
    await fetchPeriods();
  } catch {
    toast.error('No se pudo eliminar el periodo.');
  }
}

onMounted(() => fetchPeriods().catch(() => {}));
</script>
