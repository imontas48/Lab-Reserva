<template>
  <div class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <div class="w-64">
      <BaseInput :model-value="modelValue.search" name="search" label="Buscar" placeholder="Descripción, sujeto o usuario" @update:model-value="update('search', $event)" />
    </div>
    <div class="w-56">
      <BaseSelect :key="`actor-${resetKey}`" :model-value="modelValue.actor_id" name="actor_id" label="Usuario" placeholder="Todos" :options="userOptions" @update:model-value="update('actor_id', $event)" />
    </div>
    <div class="w-48">
      <BaseSelect :key="`subject-${resetKey}`" :model-value="modelValue.subject_type" name="subject_type" label="Módulo" placeholder="Todos" :options="SUBJECT_OPTIONS" @update:model-value="update('subject_type', $event)" />
    </div>
    <div class="w-56">
      <BaseSelect :key="`event-${resetKey}`" :model-value="modelValue.event" name="event" label="Acción" placeholder="Todas" :options="EVENT_OPTIONS" @update:model-value="update('event', $event)" />
    </div>
    <div class="w-40">
      <BaseInput :model-value="modelValue.from" name="from" type="date" label="Desde" @update:model-value="update('from', $event)" />
    </div>
    <div class="w-40">
      <BaseInput :model-value="modelValue.to" name="to" type="date" label="Hasta" @update:model-value="update('to', $event)" />
    </div>
    <BaseButton variant="secondary" :disabled="!hasFilters" @click="clear">Limpiar filtros</BaseButton>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import { EVENT_OPTIONS, SUBJECT_OPTIONS } from '@/utils/activityLabels';

/**
 * Filtros del registro de actividades. No consulta nada: emite el objeto de
 * filtros completo y la vista decide cuándo recargar.
 */
const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  userOptions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue']);

// La opción "Todos" de BaseSelect está deshabilitada; para volver a ella hay
// que remontar los selects.
const resetKey = ref(0);

const hasFilters = computed(() => Object.values(props.modelValue).some((value) => value !== '' && value !== null));

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value ?? '' });
}

function clear() {
  resetKey.value += 1;
  emit('update:modelValue', Object.fromEntries(Object.keys(props.modelValue).map((key) => [key, ''])));
}
</script>
