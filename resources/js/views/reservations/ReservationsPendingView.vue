<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Solicitudes pendientes</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-400">
        Peticiones de laboratorio completo a la espera de decisión. Mientras están pendientes, la franja queda bloqueada.
      </p>
    </div>

    <DataTable
      :columns="columns"
      :items="reservations"
      :loading="loading"
      :error="error"
      item-key="id"
    >
      <template #cell-user="{ item }">
        <div class="font-medium text-gray-900 dark:text-white">{{ item.user?.name ?? '—' }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.user?.email ?? '' }}</div>
      </template>

      <template #cell-lab="{ item }">
        <div class="font-medium text-gray-900 dark:text-white">{{ item.lab?.name ?? `Laboratorio #${item.lab_id}` }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.lab?.location ?? '' }}</div>
      </template>

      <template #cell-start_time="{ item }">
        <div class="text-gray-900 dark:text-white">{{ formatDateTime(item.start_time) }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">hasta {{ formatTime(item.end_time) }} · {{ item.duration_minutes }} min</div>
      </template>

      <template #cell-purpose="{ value }">
        <span class="whitespace-normal text-gray-700 dark:text-gray-300">{{ value }}</span>
      </template>

      <template #actions="{ item }">
        <ReservationApprovalActions :reservation="item" @approved="reload" @rejected="reload" />
      </template>

      <template #empty>
        <p class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">No hay solicitudes pendientes.</p>
      </template>
    </DataTable>

    <div
      v-if="paginationMeta && paginationMeta.last_page > 1"
      class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
    >
      <p class="text-sm text-gray-700 dark:text-gray-300">
        Página {{ paginationMeta.current_page }} de {{ paginationMeta.last_page }} · {{ paginationMeta.total }} solicitudes
      </p>
      <div class="flex gap-2">
        <BaseButton variant="secondary" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">‹ Anterior</BaseButton>
        <BaseButton variant="secondary" :disabled="currentPage >= paginationMeta.last_page" @click="goToPage(currentPage + 1)">Siguiente ›</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import DataTable from '@/components/ui/DataTable.vue';
import ReservationApprovalActions from '@/components/reservations/ReservationApprovalActions.vue';
import { useReservations } from '@/composables/useReservations';

/**
 * Cola de aprobación (requiere reservations.approve).
 */
const { reservations, loading, error, paginationMeta, fetchPendingReservations } = useReservations();

const currentPage = ref(1);

const columns = [
  { key: 'user', label: 'Solicitante' },
  { key: 'lab', label: 'Laboratorio' },
  { key: 'start_time', label: 'Franja' },
  { key: 'purpose', label: 'Motivo' },
];

const formatDateTime = (value) => new Intl.DateTimeFormat('es-ES', {
  weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
}).format(new Date(value));

const formatTime = (value) => new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));

async function reload() {
  await fetchPendingReservations({ page: currentPage.value });

  if (paginationMeta.value?.current_page) {
    currentPage.value = paginationMeta.value.current_page;
  }
}

function goToPage(page) {
  currentPage.value = page;
  reload();
}

onMounted(reload);
</script>
