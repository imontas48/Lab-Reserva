<template>
  <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h2 class="mb-4 flex items-center text-xl font-semibold text-gray-900 dark:text-white">
      <span class="mr-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">1</span>
      Selecciona un laboratorio
    </h2>

    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <BaseSpinner size="lg" class="text-blue-600" label="Cargando laboratorios" />
      <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Cargando laboratorios...</p>
    </div>

    <div
      v-else-if="error"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300"
      role="alert"
    >
      {{ error }}
    </div>

    <div v-else-if="activeLabs.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <button
        v-for="lab in activeLabs"
        :key="lab.id"
        type="button"
        class="group relative flex flex-col rounded-lg border border-gray-200 bg-white p-6 text-left shadow-sm transition-all hover:border-blue-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-blue-400"
        @click="$emit('select', lab)"
      >
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 group-hover:bg-blue-200">
          <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>

        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
          {{ lab.name }}
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ lab.location }}</p>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
          Capacidad: {{ lab.capacity }} {{ lab.capacity === 1 ? 'persona' : 'personas' }}
        </p>

        <svg class="absolute right-4 top-1/2 h-6 w-6 -translate-y-1/2 text-blue-600 opacity-0 transition-opacity group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </div>

    <div v-else class="py-12 text-center">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">No hay laboratorios disponibles</h3>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No se encontraron laboratorios activos en el sistema.</p>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';

/**
 * Paso 1 del asistente de reserva: elegir laboratorio.
 */
const props = defineProps({
  labs: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
});

defineEmits(['select']);

const activeLabs = computed(() => props.labs.filter((lab) => lab.is_active));
</script>
