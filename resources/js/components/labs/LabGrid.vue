<template>
  <div class="overflow-x-auto">
    <div
      class="inline-grid gap-2"
      :style="{ gridTemplateColumns: `repeat(${cols}, minmax(4.5rem, 1fr))` }"
      role="grid"
      :aria-label="`Plano del laboratorio, ${grid.rows.length} filas por ${cols} columnas`"
    >
      <template v-for="(row, r) in grid.rows" :key="r">
        <button
          v-for="(cell, c) in row"
          :key="`${r}-${c}`"
          type="button"
          role="gridcell"
          class="flex h-16 flex-col items-center justify-center rounded-md border-2 text-xs transition-shadow"
          :class="cell
            ? [STATUS_STYLES[cell.status?.status]?.classes ?? 'bg-gray-100 border-gray-300', 'hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500']
            : 'border-dashed border-gray-200 text-gray-300 dark:border-gray-700 dark:text-gray-600'"
          :title="cell ? `${cell.identifier} · ${cell.status?.details ?? ''}` : `Fila ${r + 1}, columna ${c + 1}`"
          :disabled="!cell && !selectable"
          @click="$emit('select', cell, { row: r + 1, col: c + 1 })"
        >
          <template v-if="cell">
            <span class="font-semibold">{{ cell.identifier }}</span>
            <span class="truncate px-1 text-[10px] opacity-80">{{ STATUS_STYLES[cell.status?.status]?.label ?? '' }}</span>
            <span v-if="cell.open_incidents_count" class="mt-0.5 rounded-full bg-orange-500 px-1.5 text-[10px] font-bold text-white" :title="`${cell.open_incidents_count} incidencia(s) abierta(s)`">!</span>
          </template>
          <span v-else-if="selectable">+</span>
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { STATUS_STYLES, buildGrid } from '@/utils/labGrid';

/**
 * Cuadrícula del plano. En modo `selectable` las celdas vacías también son
 * pulsables (editor de plano).
 */
const props = defineProps({
  lab: { type: Object, required: true },
  equipment: { type: Array, default: () => [] },
  selectable: { type: Boolean, default: false },
});

defineEmits(['select']);

const grid = computed(() => buildGrid(props.lab, props.equipment));
const cols = computed(() => Math.max(1, Number(props.lab?.grid_cols) || 1));

defineExpose({ grid });
</script>
