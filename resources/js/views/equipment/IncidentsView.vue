<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Incidencias</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Averías y problemas reportados por los usuarios, de mayor a menor gravedad.</p>
    </div>

    <div class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
      <div class="w-56"><BaseSelect v-model="labId" name="lab_id" label="Laboratorio" placeholder="Todos" :options="labOptions" @update:model-value="load" /></div>
      <label class="flex items-center gap-2 pb-2 text-sm text-gray-700 dark:text-gray-300">
        <input v-model="includeResolved" type="checkbox" class="h-4 w-4 rounded border-gray-300" @change="load" />
        Incluir resueltas
      </label>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div v-if="loading && incidents.length === 0" class="flex justify-center py-8"><BaseSpinner class="text-blue-600" /></div>
      <p v-else-if="error" class="text-sm text-red-600 dark:text-red-400" role="alert">{{ error }}</p>
      <IncidentList v-else :incidents="incidents" show-equipment empty-text="No hay incidencias abiertas.">
        <template #actions="{ incident }">
          <div v-if="incident.status !== 'resolved'" class="flex shrink-0 gap-2">
            <BaseButton v-if="incident.status === 'open'" variant="secondary" @click="setInProgress(incident)">Atender</BaseButton>
            <BaseButton @click="openResolve(incident)">Resolver</BaseButton>
          </div>
        </template>
      </IncidentList>
    </section>

    <BaseModal v-model="showResolve" title="Resolver incidencia" size="sm">
      <form id="resolve-form" class="space-y-4" @submit.prevent="resolve">
        <div>
          <label for="resolution" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Resolución <span class="text-red-500">*</span></label>
          <textarea id="resolution" v-model="resolution" rows="3" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
          <p v-if="resolutionError" class="mt-1 text-sm text-red-600">{{ resolutionError }}</p>
        </div>
        <BaseSelect v-model="operational" name="equipment_operational" label="Estado del equipo tras resolver" :options="OPERATIONAL_OPTIONS" />
      </form>
      <template #footer>
        <BaseButton variant="secondary" :disabled="saving" @click="showResolve = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="resolve-form" :loading="saving">Marcar como resuelta</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import IncidentList from '@/components/equipment/IncidentList.vue';
import { useIncidents } from '@/composables/useIncidents';
import { useLabs } from '@/composables/useLabs';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const { labs, fetchLabs } = useLabs();
const { incidents, loading, error, fetchIncidents, updateIncident } = useIncidents();

const OPERATIONAL_OPTIONS = [
  { value: 'keep', text: 'Sin cambios' },
  { value: 'true', text: 'Volver a poner en servicio' },
  { value: 'false', text: 'Dejar fuera de servicio' },
];

const labId = ref('');
const includeResolved = ref(false);
const showResolve = ref(false);
const target = ref(null);
const resolution = ref('');
const resolutionError = ref(null);
const operational = ref('keep');
const saving = ref(false);

const labOptions = computed(() => labs.value.map((lab) => ({ value: lab.id, text: lab.name })));

async function load() {
  const params = { per_page: 100 };
  if (labId.value) params.lab_id = labId.value;
  if (includeResolved.value) params.include_resolved = 1;

  try {
    await fetchIncidents(params);
  } catch {
    /* error.value ya está informado */
  }
}

async function setInProgress(incident) {
  try {
    await updateIncident(incident.id, { status: 'in_progress' });
    toast.success('Incidencia en atención.');
  } catch {
    toast.error('No se pudo actualizar la incidencia.');
  }
}

function openResolve(incident) {
  target.value = incident;
  resolution.value = '';
  resolutionError.value = null;
  operational.value = 'keep';
  showResolve.value = true;
}

async function resolve() {
  if (resolution.value.trim().length < 3) {
    resolutionError.value = 'Indica cómo se resolvió.';

    return;
  }

  saving.value = true;

  try {
    const payload = { status: 'resolved', resolution: resolution.value.trim() };
    if (operational.value !== 'keep') payload.equipment_operational = operational.value === 'true';

    await updateIncident(target.value.id, payload);
    toast.success('Incidencia resuelta.');
    showResolve.value = false;
    await load();
  } catch (err) {
    resolutionError.value = err?.response?.data?.errors?.resolution?.[0] ?? null;
    if (!resolutionError.value) toast.error('No se pudo resolver la incidencia.');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  fetchLabs({ per_page: 100 }).catch(() => {});
  await load();
});
</script>
