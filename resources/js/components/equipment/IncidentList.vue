<template>
  <ul class="divide-y divide-gray-100 dark:divide-gray-700">
    <li v-for="incident in incidents" :key="incident.id" class="flex items-start justify-between gap-4 py-3">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="SEVERITY_CLASSES[incident.severity]">
            {{ SEVERITY_LABELS[incident.severity] }}
          </span>
          <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="STATUS_CLASSES[incident.status]">
            {{ STATUS_LABELS[incident.status] }}
          </span>
          <router-link v-if="showEquipment && incident.equipment" :to="`/equipment/${incident.equipment.id}`" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">
            {{ incident.equipment.identifier }}<span v-if="incident.equipment.lab_name" class="text-gray-500"> · {{ incident.equipment.lab_name }}</span>
          </router-link>
        </div>
        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ incident.description }}</p>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
          {{ incident.reporter?.name ?? 'Usuario' }} · {{ formatDate(incident.created_at) }}
          <template v-if="incident.resolution"> · Resolución: {{ incident.resolution }}</template>
        </p>
      </div>
      <slot name="actions" :incident="incident" />
    </li>
    <li v-if="incidents.length === 0" class="py-4 text-sm text-gray-500 dark:text-gray-400">{{ emptyText }}</li>
  </ul>
</template>

<script setup>
defineProps({
  incidents: { type: Array, default: () => [] },
  showEquipment: { type: Boolean, default: false },
  emptyText: { type: String, default: 'Sin incidencias.' },
});

const SEVERITY_LABELS = { low: 'Baja', medium: 'Media', high: 'Alta' };
const SEVERITY_CLASSES = {
  low: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  medium: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
  high: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
};
const STATUS_LABELS = { open: 'Abierta', in_progress: 'En atención', resolved: 'Resuelta' };
const STATUS_CLASSES = {
  open: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
  in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
};

const formatDate = (value) => new Intl.DateTimeFormat('es-ES', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
</script>
