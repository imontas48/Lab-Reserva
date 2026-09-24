<template>
  <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-3 flex items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
        <p v-if="subtitle" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
      </div>
      <button
        type="button"
        class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
        :aria-pressed="showTable"
        @click="showTable = !showTable"
      >
        {{ showTable ? 'Ver gráfica' : 'Ver tabla' }}
      </button>
    </div>

    <p v-if="isEmpty" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">Sin datos en el rango elegido.</p>

    <div v-else-if="showTable" class="max-h-72 overflow-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
            <th class="py-1 pr-4">{{ labelHeader }}</th>
            <th class="py-1 text-right">{{ valueHeader }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(label, i) in labels" :key="label" class="border-t border-gray-100 dark:border-gray-700">
            <td class="py-1 pr-4 text-gray-800 dark:text-gray-200">{{ label }}</td>
            <td class="py-1 text-right tabular-nums text-gray-900 dark:text-white">{{ values[i] }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="h-64" :class="{ 'opacity-50 transition-opacity': loading }">
      <Line v-if="type === 'line'" :data="chartData" :options="options" />
      <Bar v-else :data="chartData" :options="options" />
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import {
  BarController, BarElement, CategoryScale, Chart, Filler, LineController, LineElement, LinearScale, PointElement, Tooltip,
} from 'chart.js';
import { Bar, Line } from 'vue-chartjs';
import { useThemeStore } from '@/stores/theme';
import { CHART_PALETTE } from '@/utils/reportCharts';

Chart.register(BarController, BarElement, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Filler);

/**
 * Gráfica de una sola serie (magnitud): un solo tono, marcas finas, rejilla
 * de línea fina, tooltip por marca y vista de tabla equivalente.
 */
const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  type: { type: String, default: 'bar', validator: (v) => ['bar', 'line', 'horizontalBar'].includes(v) },
  labels: { type: Array, default: () => [] },
  values: { type: Array, default: () => [] },
  labelHeader: { type: String, default: 'Categoría' },
  valueHeader: { type: String, default: 'Reservas' },
  loading: { type: Boolean, default: false },
});

const { isDark } = storeToRefs(useThemeStore());
const showTable = ref(false);

const palette = computed(() => (isDark.value ? CHART_PALETTE.dark : CHART_PALETTE.light));
const isEmpty = computed(() => props.values.length === 0 || props.values.every((v) => !v));

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [{
    label: props.valueHeader,
    data: props.values,
    backgroundColor: props.type === 'line' ? `${palette.value.series1}1a` : palette.value.series1,
    borderColor: palette.value.series1,
    borderWidth: props.type === 'line' ? 2 : 0,
    borderRadius: props.type === 'line' ? 0 : { topLeft: 4, topRight: 4, bottomLeft: props.type === 'horizontalBar' ? 0 : 0, bottomRight: props.type === 'horizontalBar' ? 4 : 0 },
    borderSkipped: props.type === 'horizontalBar' ? 'left' : 'bottom',
    maxBarThickness: 24,
    categoryPercentage: 0.7,
    fill: props.type === 'line',
    tension: 0.25,
    pointRadius: 4,
    pointHoverRadius: 6,
    pointBackgroundColor: palette.value.series1,
    pointBorderColor: palette.value.surface,
    pointBorderWidth: 2,
  }],
}));

const options = computed(() => {
  const axisCommon = {
    grid: { color: palette.value.grid, lineWidth: 1, drawTicks: false },
    border: { color: palette.value.axis, width: 1 },
    ticks: { color: palette.value.text, font: { size: 11 }, padding: 6 },
  };

  return {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: props.type === 'horizontalBar' ? 'y' : 'x',
    interaction: { mode: 'nearest', intersect: props.type !== 'line', axis: props.type === 'line' ? 'x' : 'xy' },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: isDark.value ? '#fcfcfb' : '#1a1a19',
        titleColor: isDark.value ? '#52514e' : '#c3c2b7',
        bodyColor: isDark.value ? '#0b0b0b' : '#ffffff',
        padding: 10,
        displayColors: false,
        callbacks: { label: (ctx) => `${ctx.formattedValue} ${props.valueHeader.toLowerCase()}` },
      },
    },
    scales: {
      x: { ...axisCommon, grid: { ...axisCommon.grid, display: props.type === 'horizontalBar' } },
      y: { ...axisCommon, beginAtZero: true, grid: { ...axisCommon.grid, display: props.type !== 'horizontalBar' }, ticks: { ...axisCommon.ticks, precision: 0 } },
    },
  };
});
</script>
