<template>
  <div class="space-y-6">
    <div>
      <nav class="mb-2 text-sm text-gray-500 dark:text-gray-400">
        <router-link to="/labs" class="hover:underline">Laboratorios</router-link>
        <span class="mx-1">/</span>
        <router-link :to="`/labs/${labId}`" class="hover:underline">{{ lab?.name ?? '…' }}</router-link>
        <span class="mx-1">/</span>
        <span class="text-gray-900 dark:text-white">Horario</span>
      </nav>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Horario y cierres de {{ lab?.name ?? 'laboratorio' }}</h1>
    </div>

    <div v-if="loading && !schedule" class="flex justify-center py-12"><BaseSpinner size="lg" class="text-blue-600" /></div>

    <template v-else-if="schedule">
      <OpeningHoursEditor
        :opening-hours="schedule.opening_hours"
        :saving="savingHours"
        :server-error="hoursError"
        @save="handleSaveHours"
      />

      <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cierres puntuales</h2>
        <p class="mb-4 mt-1 text-sm text-gray-600 dark:text-gray-300">
          Mantenimiento, eventos o festivos propios de este laboratorio. Los cierres globales se gestionan en Administración › Cierres.
        </p>

        <ClosureForm ref="closureForm" :saving="savingClosure" :server-error="closureError" @submit="handleCreateClosure" />

        <div class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-700">
          <ClosureList :closures="schedule.closures" can-delete :show-lab="false" :deleting-id="deletingId" @delete="handleDeleteClosure" />
        </div>
      </section>
    </template>

    <div v-else-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" role="alert">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import OpeningHoursEditor from '@/components/schedule/OpeningHoursEditor.vue';
import ClosureForm from '@/components/schedule/ClosureForm.vue';
import ClosureList from '@/components/schedule/ClosureList.vue';
import { useLabSchedule } from '@/composables/useLabSchedule';
import { useLabs } from '@/composables/useLabs';
import { useToast } from '@/composables/useToast';
import { confirmDestructive } from '@/utils/confirm';

const route = useRoute();
const toast = useToast();
const labId = computed(() => Number(route.params.id));

const { lab, fetchLabById } = useLabs();
const { schedule, loading, error, fetchSchedule, saveOpeningHours, createClosure, deleteClosure } = useLabSchedule();

const savingHours = ref(false);
const hoursError = ref(null);
const savingClosure = ref(false);
const closureError = ref(null);
const deletingId = ref(null);
const closureForm = ref(null);

async function load() {
  try {
    await Promise.all([fetchLabById(labId.value), fetchSchedule(labId.value)]);
  } catch {
    /* error.value ya está informado */
  }
}

async function handleSaveHours(hours) {
  savingHours.value = true;
  hoursError.value = null;

  try {
    await saveOpeningHours(labId.value, hours);
    toast.success('Horario guardado.');
  } catch (err) {
    hoursError.value = err?.response?.data?.message ?? 'No se pudo guardar el horario.';
  } finally {
    savingHours.value = false;
  }
}

async function handleCreateClosure(payload) {
  savingClosure.value = true;
  closureError.value = null;

  try {
    const closure = await createClosure({ ...payload, lab_id: labId.value });
    schedule.value.closures = [...schedule.value.closures, closure].sort((a, b) => a.starts_at.localeCompare(b.starts_at));
    closureForm.value?.reset();
    toast.success('Cierre añadido.');
  } catch (err) {
    closureError.value = err;
    if (err?.response?.status !== 422) toast.error('No se pudo crear el cierre.');
  } finally {
    savingClosure.value = false;
  }
}

async function handleDeleteClosure(closure) {
  const confirmed = await confirmDestructive({
    title: '¿Eliminar el cierre?',
    html: `Se eliminará el cierre <strong>${closure.reason}</strong>.`,
  });

  if (!confirmed) return;

  deletingId.value = closure.id;

  try {
    await deleteClosure(closure.id);
    schedule.value.closures = schedule.value.closures.filter((c) => c.id !== closure.id);
    toast.success('Cierre eliminado.');
  } catch {
    toast.error('No se pudo eliminar el cierre.');
  } finally {
    deletingId.value = null;
  }
}

onMounted(load);
</script>
