<template>
  <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-4 flex items-start justify-between gap-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Horario de apertura</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
          Un día desactivado está cerrado. Si ningún día está activo, el laboratorio no tiene restricción horaria.
        </p>
      </div>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-700">
      <div v-for="day in days" :key="day.weekday" class="grid grid-cols-1 items-center gap-3 py-3 sm:grid-cols-[10rem_auto_1fr_1fr]">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-200">
          <input v-model="day.enabled" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
          {{ day.label }}
        </label>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ day.enabled ? 'Abre de' : 'Cerrado' }}</span>
        <input
          v-model="day.opens_at"
          type="time"
          step="900"
          :disabled="!day.enabled"
          class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm disabled:opacity-40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        />
        <input
          v-model="day.closes_at"
          type="time"
          step="900"
          :disabled="!day.enabled"
          class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm disabled:opacity-40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        />
      </div>
    </div>

    <p v-if="localError || serverError" class="mt-3 text-sm text-red-600 dark:text-red-400" role="alert">
      {{ localError || serverError }}
    </p>

    <div class="mt-4 flex justify-end gap-2">
      <BaseButton variant="secondary" @click="applyToAllWeekdays">Copiar lunes a todos los días laborables</BaseButton>
      <BaseButton :loading="saving" @click="save">Guardar horario</BaseButton>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';

/**
 * Editor de los siete días. Emite la lista que espera la API: solo los días
 * activos, con horas HH:MM.
 */
const props = defineProps({
  /** [{ weekday, opens_at, closes_at }] tal como lo devuelve la API */
  openingHours: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
  serverError: { type: String, default: null },
});

const emit = defineEmits(['save']);

const LABELS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
const ORDER = [1, 2, 3, 4, 5, 6, 0];

const days = ref(buildDays(props.openingHours));

watch(() => props.openingHours, (hours) => { days.value = buildDays(hours); });

function buildDays(hours) {
  return ORDER.map((weekday) => {
    const row = hours.find((h) => h.weekday === weekday);

    return {
      weekday,
      label: LABELS[weekday],
      enabled: !!row,
      opens_at: row?.opens_at ?? '08:00',
      closes_at: row?.closes_at ?? '18:00',
    };
  });
}

const localError = computed(() => {
  const invalid = days.value.find((d) => d.enabled && (!d.opens_at || !d.closes_at || d.closes_at <= d.opens_at));

  return invalid ? `${invalid.label}: la hora de cierre debe ser posterior a la de apertura.` : null;
});

function applyToAllWeekdays() {
  const monday = days.value.find((d) => d.weekday === 1);

  days.value.forEach((d) => {
    if (d.weekday >= 1 && d.weekday <= 5) {
      d.enabled = monday.enabled;
      d.opens_at = monday.opens_at;
      d.closes_at = monday.closes_at;
    }
  });
}

function save() {
  if (localError.value) return;

  emit('save', days.value
    .filter((d) => d.enabled)
    .map(({ weekday, opens_at, closes_at }) => ({ weekday, opens_at, closes_at })));
}
</script>
