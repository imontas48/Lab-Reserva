<template>
  <DetailPanel
    title="Detalle de Reserva"
    :subtitle="item?.equipment?.identifier"
    :loading="loading"
    :error="error"
    :fields="fields"
    @retry="load"
  />
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import DetailPanel from '@/components/ui/DetailPanel.vue';
import { useResource } from '@/composables/useResource';

const route = useRoute();

// Se usa useResource directamente: useReservations sigue el dialecto antiguo y
// no expone una carga por id.
const { item, loading, error, fetchById } = useResource('reservations', {
  singular: 'reserva',
  plural: 'reservas',
});

const STATUS_LABELS = {
  confirmed: 'Confirmada',
  cancelled: 'Cancelada',
  completed: 'Completada',
};

function formatDateTime(value) {
  if (!value) {
    return null;
  }

  return new Intl.DateTimeFormat('es', {
    dateStyle: 'long',
    timeStyle: 'short',
  }).format(new Date(value));
}

const fields = computed(() => (item.value ? [
  { label: 'Equipo', value: item.value.equipment?.identifier },
  { label: 'Laboratorio', value: item.value.equipment?.lab?.name },
  { label: 'Inicio', value: formatDateTime(item.value.start_time) },
  { label: 'Fin', value: formatDateTime(item.value.end_time) },
  { label: 'Estado', value: STATUS_LABELS[item.value.status] ?? item.value.status },
  { label: 'Duración', value: item.value.duration_minutes ? `${item.value.duration_minutes} min` : null },
] : []));

async function load() {
  try {
    await fetchById(route.params.id);
  } catch {
    /* el panel muestra error.value */
  }
}

onMounted(load);
</script>
