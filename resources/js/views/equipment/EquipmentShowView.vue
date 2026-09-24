<template>
  <div class="space-y-6">
    <DetailPanel
      title="Detalle de Equipo"
      :subtitle="equipmentItem?.identifier"
      :loading="loading"
      :error="error"
      :fields="fields"
      @retry="load"
    >
      <template #actions>
        <div class="flex flex-wrap gap-2">
          <BaseButton v-if="authStore.can('incidents.create')" variant="secondary" @click="showReport = true">Reportar incidencia</BaseButton>
          <BaseButton v-if="authStore.isAdmin" variant="secondary" @click="router.push(`/equipment/${route.params.id}/edit`)">
            Editar
          </BaseButton>
        </div>
      </template>
    </DetailPanel>

    <section v-if="equipmentItem" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Software instalado</h2>
      <div v-if="equipmentItem.software?.length" class="mt-3 flex flex-wrap gap-2">
        <span v-for="sw in equipmentItem.software" :key="sw.id" class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
          {{ sw.name }}<span v-if="sw.version" class="text-gray-500"> {{ sw.version }}</span>
        </span>
      </div>
      <p v-else class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sin software registrado.</p>
    </section>

    <section v-if="equipmentItem" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Incidencias</h2>
      <IncidentList :incidents="incidents" empty-text="Este equipo no tiene incidencias registradas." />
    </section>

    <IncidentReportModal
      v-model="showReport"
      :equipment-name="equipmentItem?.identifier ?? ''"
      :loading="reporting"
      :server-error="reportError"
      @submit="handleReport"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import DetailPanel from '@/components/ui/DetailPanel.vue';
import IncidentList from '@/components/equipment/IncidentList.vue';
import IncidentReportModal from '@/components/equipment/IncidentReportModal.vue';
import { useEquipment } from '@/composables/useEquipment';
import { useIncidents } from '@/composables/useIncidents';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();
const { equipmentItem, loading, error, fetchEquipmentById } = useEquipment();
const { incidents, fetchForEquipment, reportIncident } = useIncidents();

const showReport = ref(false);
const reporting = ref(false);
const reportError = ref(null);

const fields = computed(() => (equipmentItem.value ? [
  { label: 'Identificador', value: equipmentItem.value.identifier },
  { label: 'Laboratorio', value: equipmentItem.value.lab?.name },
  { label: 'Tipo', value: equipmentItem.value.type },
  { label: 'Estado', value: equipmentItem.value.status?.details },
  { label: 'Operativo', value: equipmentItem.value.is_operational ? 'Sí' : 'No' },
  { label: 'Posición en el plano', value: equipmentItem.value.grid_row ? `Fila ${equipmentItem.value.grid_row}, columna ${equipmentItem.value.grid_col}` : null },
  { label: 'Especificaciones', value: equipmentItem.value.specifications },
] : []));

async function load() {
  try {
    await fetchEquipmentById(route.params.id);
    await fetchForEquipment(route.params.id);
  } catch {
    /* el panel muestra error.value */
  }
}

async function handleReport(payload) {
  reporting.value = true;
  reportError.value = null;

  try {
    await reportIncident(route.params.id, payload);
    toast.success('Incidencia reportada. Gracias por avisar.');
    showReport.value = false;
  } catch (err) {
    reportError.value = err;
    if (err?.response?.status !== 422) toast.error('No se pudo enviar el reporte.');
  } finally {
    reporting.value = false;
  }
}

onMounted(load);
</script>
