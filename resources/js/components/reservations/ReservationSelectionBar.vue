<template>
  <div class="rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-green-50 p-4 dark:border-blue-800 dark:from-blue-900/30 dark:to-green-900/30">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-4">
        <div>
          <p class="text-xs font-medium text-blue-900 dark:text-blue-300">Laboratorio</p>
          <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">{{ lab.name }}</p>
        </div>

        <span class="text-gray-400">›</span>

        <div v-if="mode === 'lab'">
          <p class="text-xs font-medium text-purple-900 dark:text-purple-300">Modalidad</p>
          <p class="text-sm font-semibold text-purple-900 dark:text-purple-100">Laboratorio completo (clase)</p>
        </div>
        <div v-else-if="equipment">
          <p class="text-xs font-medium text-green-900 dark:text-green-300">Equipo</p>
          <p class="text-sm font-semibold text-green-900 dark:text-green-100">{{ equipment.identifier }}</p>
          <span
            v-if="equipment.status"
            class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
            :class="statusBadgeClasses"
          >
            {{ equipment.status.details }}
          </span>
        </div>
      </div>

      <div class="flex gap-2">
        <BaseButton v-if="mode === 'equipment'" variant="secondary" @click="$emit('change-equipment')">
          Cambiar equipo
        </BaseButton>
        <BaseButton variant="secondary" @click="$emit('change-lab')">
          Cambiar laboratorio
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';

/**
 * Resumen de lo elegido en los pasos anteriores del asistente.
 */
const props = defineProps({
  lab: { type: Object, required: true },
  mode: { type: String, required: true, validator: (v) => ['equipment', 'lab'].includes(v) },
  equipment: { type: Object, default: null },
});

defineEmits(['change-lab', 'change-equipment']);

const statusBadgeClasses = computed(() => ({
  green: 'bg-green-100 text-green-800',
  blue: 'bg-blue-100 text-blue-800',
  yellow: 'bg-yellow-100 text-yellow-800',
  red: 'bg-red-100 text-red-800',
}[props.equipment?.status?.color] ?? 'bg-gray-100 text-gray-800'));
</script>
