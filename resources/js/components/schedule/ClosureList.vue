<template>
  <div>
    <p v-if="closures.length === 0" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
      No hay cierres programados.
    </p>
    <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
      <li v-for="closure in closures" :key="closure.id" class="flex items-start justify-between gap-4 py-3">
        <div>
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            {{ closure.reason }}
            <span
              v-if="closure.is_global"
              class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300"
            >
              Todos los laboratorios
            </span>
            <span v-else-if="showLab && closure.lab_name" class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ closure.lab_name }}</span>
          </p>
          <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
            {{ formatRange(closure.starts_at, closure.ends_at) }}
          </p>
        </div>
        <BaseButton v-if="canDelete" variant="danger" :loading="deletingId === closure.id" @click="$emit('delete', closure)">
          Eliminar
        </BaseButton>
      </li>
    </ul>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue';

defineProps({
  closures: { type: Array, default: () => [] },
  canDelete: { type: Boolean, default: false },
  showLab: { type: Boolean, default: true },
  deletingId: { type: Number, default: null },
});

defineEmits(['delete']);

const fmt = new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
const formatRange = (start, end) => `${fmt.format(new Date(start))} → ${fmt.format(new Date(end))}`;
</script>
