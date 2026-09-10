<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cierres</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-400">
        Festivos y cierres que afectan a todos los laboratorios o a uno concreto. Durante un cierre no se puede reservar.
      </p>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Nuevo cierre</h2>
      <ClosureForm ref="form" :lab-options="labOptions" :saving="saving" :server-error="serverError" @submit="handleCreate" />
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="mb-2 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Próximos cierres</h2>
        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
          <input v-model="includePast" type="checkbox" class="h-4 w-4 rounded border-gray-300" @change="load" />
          Incluir pasados
        </label>
      </div>
      <div v-if="loading && closures.length === 0" class="flex justify-center py-8"><BaseSpinner class="text-blue-600" /></div>
      <ClosureList v-else :closures="closures" can-delete :deleting-id="deletingId" @delete="handleDelete" />
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import ClosureForm from '@/components/schedule/ClosureForm.vue';
import ClosureList from '@/components/schedule/ClosureList.vue';
import { useLabSchedule } from '@/composables/useLabSchedule';
import { useLabs } from '@/composables/useLabs';
import { useToast } from '@/composables/useToast';
import { confirmDestructive } from '@/utils/confirm';

const toast = useToast();
const { labs, fetchLabs } = useLabs();
const { closures, loading, fetchClosures, createClosure, deleteClosure } = useLabSchedule();

const form = ref(null);
const saving = ref(false);
const serverError = ref(null);
const deletingId = ref(null);
const includePast = ref(false);

const labOptions = computed(() => labs.value.map((lab) => ({ value: lab.id, text: lab.name })));

async function load() {
  try {
    await fetchClosures({ include_past: includePast.value ? 1 : 0, per_page: 100 });
  } catch {
    toast.error('No se pudieron cargar los cierres.');
  }
}

async function handleCreate(payload) {
  saving.value = true;
  serverError.value = null;

  try {
    await createClosure(payload);
    form.value?.reset();
    toast.success('Cierre creado.');
  } catch (err) {
    serverError.value = err;
    if (err?.response?.status !== 422) toast.error('No se pudo crear el cierre.');
  } finally {
    saving.value = false;
  }
}

async function handleDelete(closure) {
  const confirmed = await confirmDestructive({
    title: '¿Eliminar el cierre?',
    html: `Se eliminará el cierre <strong>${closure.reason}</strong>.`,
  });

  if (!confirmed) return;

  deletingId.value = closure.id;

  try {
    await deleteClosure(closure.id);
    toast.success('Cierre eliminado.');
  } catch {
    toast.error('No se pudo eliminar el cierre.');
  } finally {
    deletingId.value = null;
  }
}

onMounted(async () => {
  try {
    await fetchLabs({ per_page: 100 });
  } catch {
    /* el formulario funciona sin la lista: cierre global */
  }
  await load();
});
</script>
