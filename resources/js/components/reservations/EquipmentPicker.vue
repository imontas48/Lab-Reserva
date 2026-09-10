<template>
  <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h2 class="mb-4 flex items-center text-xl font-semibold text-gray-900 dark:text-white">
      <span class="mr-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-600">{{ stepNumber }}</span>
      Selecciona un equipo de {{ lab.name }}
    </h2>

    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <BaseSpinner size="lg" class="text-blue-600" label="Cargando equipos" />
      <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Cargando equipos del laboratorio...</p>
    </div>

    <div
      v-else-if="error"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300"
      role="alert"
    >
      {{ error }}
    </div>

    <div v-else-if="equipment.length > 0">
      <BaseSelect
        v-if="softwareOptions.length > 0"
        v-model="softwareFilter"
        label="Necesito un equipo con…"
        placeholder="Cualquier software"
        :options="softwareOptions"
        name="software_filter"
      />

      <BaseSelect
        v-model="selectedId"
        label="Equipo"
        placeholder="-- Selecciona un equipo --"
        :options="equipmentOptions"
        name="equipment_id"
      />

      <p v-if="softwareFilter && equipmentOptions.length === 0" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Ningún equipo de este laboratorio tiene ese software.
      </p>

      <div v-if="selected" class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700">
        <div class="flex items-center justify-between gap-4">
          <p class="text-base font-semibold text-gray-900 dark:text-white">{{ selected.identifier }}</p>
          <span
            v-if="selected.status"
            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
            :class="statusBadgeClasses"
          >
            {{ selected.status.details }}
          </span>
        </div>

        <p v-if="hasOtherReservations" class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
          Este equipo tiene reservas en algunos horarios. En el calendario podrás elegir una franja libre.
        </p>
        <p v-else-if="isOutOfService" class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
          Este equipo está en mantenimiento y no puede ser reservado. Selecciona otro.
        </p>

        <BaseButton v-if="!isOutOfService" block class="mt-4" @click="$emit('select', selected)">
          Continuar con este equipo →
        </BaseButton>
      </div>
    </div>

    <div v-else class="py-12 text-center">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">No hay equipos en este laboratorio</h3>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Este laboratorio no tiene equipos asociados en el sistema.</p>
    </div>

    <BaseButton variant="ghost" class="mt-6" @click="$emit('back')">
      ← Volver
    </BaseButton>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';

/**
 * Paso del asistente: elegir un equipo del laboratorio.
 */
const props = defineProps({
  lab: { type: Object, required: true },
  equipment: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
  stepNumber: { type: Number, default: 2 },
});

defineEmits(['select', 'back']);

const selectedId = ref('');
const softwareFilter = ref('');

// Software presente en algún equipo del laboratorio, para filtrar en cliente.
const softwareOptions = computed(() => {
  const seen = new Map();
  props.equipment.forEach((eq) => (eq.software ?? []).forEach((sw) => seen.set(sw.id, sw)));

  return [...seen.values()]
    .sort((a, b) => a.name.localeCompare(b.name))
    .map((sw) => ({ value: sw.id, text: sw.version ? `${sw.name} ${sw.version}` : sw.name }));
});

const filteredEquipment = computed(() => (softwareFilter.value
  ? props.equipment.filter((eq) => (eq.software ?? []).some((sw) => String(sw.id) === String(softwareFilter.value)))
  : props.equipment));

const equipmentOptions = computed(() => filteredEquipment.value.map((eq) => ({
  value: eq.id,
  text: `${eq.identifier} — ${eq.status?.details ?? 'Sin estado'}${eq.software?.length ? ` · ${eq.software.map((s) => s.name).join(', ')}` : ''}`,
  disabled: eq.status?.status === 'out_of_service',
})));

const selected = computed(() => props.equipment.find((eq) => String(eq.id) === String(selectedId.value)) ?? null);

const isOutOfService = computed(() => selected.value?.status?.status === 'out_of_service');
const hasOtherReservations = computed(() => ['reserved', 'in_use'].includes(selected.value?.status?.status));

const statusBadgeClasses = computed(() => ({
  green: 'bg-green-100 text-green-800',
  blue: 'bg-blue-100 text-blue-800',
  yellow: 'bg-yellow-100 text-yellow-800',
  red: 'bg-red-100 text-red-800',
}[selected.value?.status?.color] ?? 'bg-gray-100 text-gray-800'));
</script>
