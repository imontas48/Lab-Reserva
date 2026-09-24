<template>
  <div class="rounded-lg border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
    <label class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-200">
      <input
        type="checkbox"
        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        :checked="enabled"
        @change="$emit('update:enabled', $event.target.checked)"
      />
      Repetir cada semana (clase recurrente)
    </label>

    <div v-if="enabled" class="mt-3 space-y-3">
      <BaseInput
        :model-value="until"
        name="repeat_until"
        type="date"
        label="Hasta el"
        :error="error"
        required
        @update:model-value="$emit('update:until', $event)"
      />

      <fieldset>
        <legend class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Días de la semana</legend>
        <div class="flex flex-wrap gap-2">
          <label
            v-for="day in DAYS"
            :key="day.value"
            class="inline-flex cursor-pointer items-center gap-1 rounded-full border px-3 py-1 text-xs font-medium"
            :class="selected.includes(day.value)
              ? 'border-indigo-500 bg-indigo-600 text-white'
              : 'border-gray-300 bg-white text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200'"
          >
            <input type="checkbox" class="sr-only" :checked="selected.includes(day.value)" @change="toggle(day.value)" />
            {{ day.label }}
          </label>
        </div>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          Si no eliges ninguno, se repite el {{ firstDayName }}. Las fechas ocupadas o cerradas se omiten y se te informará.
        </p>
      </fieldset>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import BaseInput from '@/components/forms/BaseInput.vue';

/**
 * Campos de recurrencia semanal del modal de reserva de laboratorio.
 */
const props = defineProps({
  enabled: { type: Boolean, default: false },
  until: { type: String, default: '' },
  weekdays: { type: Array, default: () => [] },
  /** ISO de la primera clase; da el día por defecto. */
  firstDate: { type: String, required: true },
  error: { type: String, default: null },
});

const emit = defineEmits(['update:enabled', 'update:until', 'update:weekdays']);

const DAYS = [
  { value: 1, label: 'L' }, { value: 2, label: 'M' }, { value: 3, label: 'X' },
  { value: 4, label: 'J' }, { value: 5, label: 'V' }, { value: 6, label: 'S' }, { value: 0, label: 'D' },
];

const selected = computed(() => props.weekdays);

const firstDayName = computed(() => new Date(props.firstDate).toLocaleDateString('es-ES', { weekday: 'long' }));

function toggle(value) {
  const next = selected.value.includes(value)
    ? selected.value.filter((v) => v !== value)
    : [...selected.value, value];

  emit('update:weekdays', next);
}
</script>
