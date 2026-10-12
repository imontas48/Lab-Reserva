<template>
  <BaseModal :model-value="modelValue" title="Detalle de la actividad" size="lg" @update:model-value="emit('update:modelValue', $event)">
    <div v-if="log" class="space-y-4 text-sm">
      <dl class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
        <div v-for="item in summary" :key="item.label">
          <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ item.label }}</dt>
          <dd class="mt-0.5 break-words text-gray-900 dark:text-gray-100">{{ item.value }}</dd>
        </div>
      </dl>

      <p class="rounded-md bg-gray-50 px-3 py-2 text-gray-800 dark:bg-gray-700/50 dark:text-gray-200">{{ log.description }}</p>

      <div v-if="rows.length" class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Campo</th>
              <th v-if="hasBefore" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Antes</th>
              <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ hasBefore ? 'Después' : 'Valor' }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="row in rows" :key="row.field">
              <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-700 dark:text-gray-300" :title="row.field">{{ row.label }}</td>
              <td v-if="hasBefore" class="break-all px-4 py-2 text-red-700 dark:text-red-300">{{ row.before }}</td>
              <td class="break-all px-4 py-2" :class="hasBefore ? 'text-green-700 dark:text-green-300' : 'text-gray-900 dark:text-gray-100'">{{ row.after }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <template #footer>
      <BaseButton variant="secondary" @click="emit('update:modelValue', false)">Cerrar</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import { eventLabel, formatTimestamp, propertyRows, subjectLabel } from '@/utils/activityLabels';

/**
 * Detalle de solo lectura de una entrada del registro.
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  log: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const rows = computed(() => propertyRows(props.log?.properties));
const hasBefore = computed(() => rows.value.some((row) => row.before !== null));

const summary = computed(() => {
  const log = props.log;
  if (!log) return [];

  const actor = log.actor
    ? `${log.actor.name} <${log.actor.email}>${log.actor.deleted ? ' (cuenta eliminada)' : ''}`
    : 'Sistema';
  const subject = log.subject_type
    ? `${subjectLabel(log.subject_type)} ${log.subject_label ?? (log.subject_id ? `#${log.subject_id}` : '')}`.trim()
    : '—';

  return [
    { label: 'Fecha', value: formatTimestamp(log.created_at) },
    { label: 'Usuario', value: actor },
    { label: 'Acción', value: eventLabel(log.event) },
    { label: 'Sujeto', value: subject },
    { label: 'Origen', value: log.source === 'console' ? 'Sistema (consola o tarea programada)' : 'Aplicación web' },
    { label: 'IP', value: log.ip_address ?? '—' },
    { label: 'Navegador', value: log.user_agent ?? '—' },
    { label: 'Registro', value: `#${log.id}` },
  ];
});
</script>
