<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="classes"
  >
    <BaseSpinner v-if="loading" size="sm" class="mr-2" />
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';
import BaseSpinner from './BaseSpinner.vue';

/**
 * Botón de la aplicación.
 *
 * Cada botón repetía a mano la misma cadena de ~10 clases de Tailwind, y el
 * estado de carga se resolvía de forma distinta en cada vista.
 */
const props = defineProps({
  type: {
    type: String,
    default: 'button',
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'danger', 'ghost'].includes(value),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  block: {
    type: Boolean,
    default: false,
  },
});

const VARIANTS = {
  primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
  secondary: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-indigo-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700',
  danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
  ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-400 dark:text-gray-300 dark:hover:bg-gray-700',
};

const classes = computed(() => [
  'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium',
  'transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2',
  'disabled:cursor-not-allowed disabled:opacity-60',
  'dark:focus:ring-offset-gray-800',
  VARIANTS[props.variant],
  props.block ? 'w-full' : '',
]);
</script>
