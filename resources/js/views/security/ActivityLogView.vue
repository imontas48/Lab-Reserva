<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Registro de actividad</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Qué hizo cada usuario, cuándo y desde dónde. Es de solo lectura: ninguna entrada se puede editar ni borrar.</p>
    </div>

    <ActivityLogFilters v-model="filters" :user-options="userOptions" />

    <DataTable :columns="columns" :items="logs" :loading="loading && logs.length === 0" :error="error" item-key="id">
      <template #cell-created_at="{ value }">
        <span class="tabular-nums text-gray-700 dark:text-gray-300">{{ formatTimestamp(value) }}</span>
      </template>
      <template #cell-actor="{ item }">
        <template v-if="item.actor">
          <div class="font-medium text-gray-900 dark:text-white">{{ item.actor.name }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.actor.email }}<span v-if="item.actor.deleted"> · eliminada</span></div>
        </template>
        <span v-else class="italic text-gray-500 dark:text-gray-400">Sistema</span>
      </template>
      <template #cell-event="{ value }">
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="eventClass(value)">{{ eventLabel(value) }}</span>
      </template>
      <template #cell-description="{ item }">
        <div class="max-w-md truncate text-gray-900 dark:text-gray-100" :title="item.description">{{ item.description }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ subjectLabel(item.subject_type) }}</div>
      </template>
      <template #actions="{ item }">
        <button type="button" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400" @click="open(item)">Ver detalle</button>
      </template>
      <template #empty>
        <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">No hay actividad con esos filtros.</p>
      </template>
    </DataTable>

    <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-gray-600 dark:text-gray-400">Página {{ meta.current_page }} de {{ meta.last_page }} · {{ meta.total }} entradas</p>
      <div class="flex gap-2">
        <BaseButton variant="secondary" :disabled="page <= 1 || loading" @click="goTo(page - 1)">‹ Anterior</BaseButton>
        <BaseButton variant="secondary" :disabled="page >= meta.last_page || loading" @click="goTo(page + 1)">Siguiente ›</BaseButton>
      </div>
    </div>

    <ActivityLogDetail v-model="showDetail" :log="selected" />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import DataTable from '@/components/ui/DataTable.vue';
import ActivityLogDetail from '@/components/security/ActivityLogDetail.vue';
import ActivityLogFilters from '@/components/security/ActivityLogFilters.vue';
import { useActivityLogs } from '@/composables/useActivityLogs';
import { useUsers } from '@/composables/useUsers';
import { eventClass, eventLabel, formatTimestamp, subjectLabel } from '@/utils/activityLabels';

const route = useRoute();
const { logs, meta, loading, error, fetchActivityLogs } = useActivityLogs();
const { users, fetchUsers } = useUsers();

const columns = [
  { key: 'created_at', label: 'Fecha' },
  { key: 'actor', label: 'Usuario' },
  { key: 'event', label: 'Acción' },
  { key: 'description', label: 'Descripción' },
  { key: 'ip_address', label: 'IP' },
];

// Se puede llegar ya filtrado desde el detalle de un usuario (?actor_id=).
const filters = ref({
  search: '',
  actor_id: route.query.actor_id ? String(route.query.actor_id) : '',
  subject_type: '',
  event: '',
  from: '',
  to: '',
});
const page = ref(1);
const showDetail = ref(false);
const selected = ref(null);
let debounceTimer = null;

const userOptions = computed(() => users.value.map((user) => ({ value: String(user.id), text: user.name })));

async function load() {
  const params = { page: page.value, per_page: 25 };

  for (const [key, value] of Object.entries(filters.value)) {
    if (value !== '' && value !== null) params[key] = value;
  }

  try {
    await fetchActivityLogs(params);
  } catch {
    /* error.value ya está informado */
  }
}

function goTo(target) {
  page.value = target;
  load();
}

function open(log) {
  selected.value = log;
  showDetail.value = true;
}

// Un solo punto de recarga para cualquier filtro, con espera para que
// escribir en "Buscar" no lance una petición por tecla.
watch(filters, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => goTo(1), 350);
}, { deep: true });

onUnmounted(() => clearTimeout(debounceTimer));

onMounted(() => {
  fetchUsers({ per_page: 100, sort_by: 'name', sort_order: 'asc' }).catch(() => {});
  load();
});
</script>
