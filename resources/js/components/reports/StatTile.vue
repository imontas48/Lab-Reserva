<template>
  <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ label }}</p>
    <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ formatted }}</p>
    <p v-if="hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Cifra destacada: etiqueta en minúscula, valor en la misma sans que el
 * resto, cifras proporcionales (no tabulares) y un matiz opcional debajo.
 */
const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], default: null },
  suffix: { type: String, default: '' },
  hint: { type: String, default: '' },
});

const formatted = computed(() => {
  if (props.value === null || props.value === undefined) return '—';
  if (typeof props.value === 'number') return `${new Intl.NumberFormat('es-ES').format(props.value)}${props.suffix}`;

  return `${props.value}${props.suffix}`;
});
</script>
