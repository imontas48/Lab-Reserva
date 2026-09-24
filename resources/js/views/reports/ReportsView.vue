<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reportes de uso</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Ocupación, asistencia y actividad de los laboratorios.</p>
      </div>
      <BaseButton variant="secondary" :loading="exporting" @click="handleExport">Exportar CSV</BaseButton>
    </div>

    <!-- Filtros: una sola fila, por encima de todo lo que acotan -->
    <div class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
      <div class="flex gap-1">
        <button
          v-for="preset in PRESETS"
          :key="preset.days"
          type="button"
          class="rounded-md px-3 py-1.5 text-sm"
          :class="activePreset === preset.days
            ? 'bg-blue-600 font-medium text-white'
            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
          @click="applyPreset(preset.days)"
        >
          {{ preset.label }}
        </button>
      </div>
      <div class="w-44"><BaseInput v-model="from" name="from" type="date" label="Desde" @update:model-value="activePreset = null" /></div>
      <div class="w-44"><BaseInput v-model="to" name="to" type="date" label="Hasta" @update:model-value="activePreset = null" /></div>
      <div class="w-60"><BaseSelect v-model="labId" name="lab_id" label="Laboratorio" placeholder="Todos" :options="labOptions" /></div>
      <BaseButton :loading="loading" @click="load">Aplicar</BaseButton>
    </div>

    <div v-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" role="alert">{{ error }}</div>

    <template v-if="summary">
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4" :class="{ 'opacity-50 transition-opacity': loading }">
        <StatTile label="Reservas en el periodo" :value="summary.total" />
        <StatTile label="Horas reservadas" :value="summary.booked_hours" suffix=" h" hint="Confirmadas y completadas" />
        <StatTile
          label="Tasa de inasistencia"
          :value="summary.attendance.no_show_rate"
          :suffix="summary.attendance.no_show_rate === null ? '' : ' %'"
          :hint="`${summary.attendance.no_shows} de ${summary.attendance.attended + summary.attendance.no_shows} con check-in`"
        />
        <StatTile label="Clases (laboratorio completo)" :value="summary.by_type.lab" :hint="`${summary.by_type.equipment} reservas de equipo`" />
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <ReportChart title="Horas reservadas por día" type="line" :labels="day.labels" :values="day.values" label-header="Día" value-header="Horas" :loading="loading" />
        <ReportChart title="Reservas por hora de inicio" :labels="hour.labels" :values="hour.values" label-header="Hora" :loading="loading" />
        <ReportChart title="Reservas por día de la semana" :labels="weekday.labels" :values="weekday.values" label-header="Día" :loading="loading" />
        <ReportChart title="Reservas por estado" type="horizontalBar" :labels="status.labels" :values="status.values" label-header="Estado" :loading="loading" />
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <RankingTable title="Laboratorios más usados" :rows="summary.top_labs" name-key="lab_name" :columns="[{ key: 'reservations', label: 'Reservas' }, { key: 'hours', label: 'Horas' }]" />
        <RankingTable title="Usuarios más activos" :rows="summary.top_users" name-key="name" :columns="[{ key: 'role', label: 'Rol' }, { key: 'reservations', label: 'Reservas' }]" />
      </div>
    </template>

    <div v-else-if="loading" class="flex justify-center py-12"><BaseSpinner size="lg" class="text-blue-600" /></div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import ReportChart from '@/components/charts/ReportChart.vue';
import RankingTable from '@/components/reports/RankingTable.vue';
import StatTile from '@/components/reports/StatTile.vue';
import { useLabs } from '@/composables/useLabs';
import { useReports } from '@/composables/useReports';
import { useToast } from '@/composables/useToast';
import { toLocalDateString } from '@/utils/datetime';
import { daySeries, hourSeries, statusSeries, weekdaySeries } from '@/utils/reportCharts';

const toast = useToast();
const { labs, fetchLabs } = useLabs();
const { summary, occupancy, loading, exporting, error, fetchAll, exportCsv } = useReports();

const PRESETS = [
  { days: 7, label: '7 días' },
  { days: 30, label: '30 días' },
  { days: 90, label: '90 días' },
];

const activePreset = ref(30);
const from = ref('');
const to = ref('');
const labId = ref('');

const labOptions = computed(() => labs.value.map((lab) => ({ value: lab.id, text: lab.name })));
const params = computed(() => ({ from: from.value, to: to.value, ...(labId.value ? { lab_id: labId.value } : {}) }));

const day = computed(() => daySeries(occupancy.value?.by_day, 'hours'));
const hour = computed(() => hourSeries(occupancy.value?.by_hour));
const weekday = computed(() => weekdaySeries(occupancy.value?.by_weekday));
const status = computed(() => statusSeries(summary.value?.by_status));

function applyPreset(days) {
  activePreset.value = days;
  const end = new Date();
  const start = new Date();
  start.setDate(end.getDate() - days);
  from.value = toLocalDateString(start);
  to.value = toLocalDateString(end);
  load();
}

async function load() {
  try {
    await fetchAll(params.value);
  } catch {
    /* error.value ya está informado */
  }
}

async function handleExport() {
  try {
    await exportCsv(params.value);
  } catch {
    toast.error('No se pudo generar la exportación.');
  }
}

onMounted(async () => {
  fetchLabs({ per_page: 100 }).catch(() => {});
  applyPreset(30);
});
</script>
