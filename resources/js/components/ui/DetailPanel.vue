<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h1>
        <p v-if="subtitle" class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ subtitle }}</p>
      </div>
      <slot name="actions" />
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <BaseSpinner size="lg" class="text-indigo-600" />
    </div>

    <div
      v-else-if="error"
      class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300"
      role="alert"
    >
      {{ error }}
      <button class="ml-2 font-medium underline" @click="$emit('retry')">Reintentar</button>
    </div>

    <dl
      v-else
      class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-lg border border-gray-200 bg-white p-6 sm:grid-cols-2 dark:border-gray-700 dark:bg-gray-800"
    >
      <div v-for="field in fields" :key="field.label">
        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
          {{ field.label }}
        </dt>
        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
          {{ field.value ?? '—' }}
        </dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import BaseSpinner from './BaseSpinner.vue';

/**
 * Panel de detalle de un recurso.
 *
 * Existía una vista stub por cada recurso —solo un <h1> y un TODO— y el router
 * y el dashboard enlazaban a ellas, de modo que el usuario llegaba a páginas en
 * blanco. Este componente concentra la estructura para que cada vista solo
 * tenga que decir qué campos mostrar.
 */
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
  /** @type {{ label: string, value: unknown }[]} */
  fields: { type: Array, default: () => [] },
});

defineEmits(['retry']);
</script>
