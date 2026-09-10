<template>
  <div class="space-y-6">
    <DetailPanel
      title="Detalle de Laboratorio"
      :subtitle="lab?.name"
      :loading="loading"
      :error="error"
      :fields="fields"
      @retry="load"
    >
      <template #actions>
        <div class="flex flex-wrap gap-2">
          <BaseButton variant="secondary" @click="router.push(`/labs/${route.params.id}/map`)">
            Mapa en tiempo real
          </BaseButton>
          <BaseButton v-if="authStore.isAdmin" variant="secondary" @click="router.push(`/labs/${route.params.id}/layout`)">
            Plano
          </BaseButton>
          <BaseButton v-if="authStore.can('schedule.manage')" variant="secondary" @click="router.push(`/labs/${route.params.id}/schedule`)">
            Horario y cierres
          </BaseButton>
          <BaseButton v-if="authStore.isAdmin" variant="secondary" @click="router.push(`/labs/${route.params.id}/edit`)">
            Editar
          </BaseButton>
        </div>
      </template>
    </DetailPanel>

    <section v-if="schedule" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Horario de apertura</h2>
      <p v-if="schedule.opening_hours.length === 0" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
        Sin restricción horaria: se puede reservar a cualquier hora.
      </p>
      <dl v-else class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 text-sm sm:grid-cols-4">
        <template v-for="row in schedule.opening_hours" :key="row.weekday">
          <dt class="capitalize text-gray-500 dark:text-gray-400">{{ row.weekday_name }}</dt>
          <dd class="text-gray-900 dark:text-white">{{ row.opens_at }} – {{ row.closes_at }}</dd>
        </template>
      </dl>

      <template v-if="schedule.closures.length > 0">
        <h3 class="mt-5 text-sm font-semibold text-gray-900 dark:text-white">Próximos cierres</h3>
        <ClosureList :closures="schedule.closures" :show-lab="false" />
      </template>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import DetailPanel from '@/components/ui/DetailPanel.vue';
import ClosureList from '@/components/schedule/ClosureList.vue';
import { useLabs } from '@/composables/useLabs';
import { useLabSchedule } from '@/composables/useLabSchedule';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { lab, loading, error, fetchLabById } = useLabs();
const { schedule, fetchSchedule } = useLabSchedule();

const fields = computed(() => (lab.value ? [
  { label: 'Nombre', value: lab.value.name },
  { label: 'Ubicación', value: lab.value.location },
  { label: 'Capacidad', value: `${lab.value.capacity} personas` },
  { label: 'Estado', value: lab.value.is_active ? 'Activo' : 'Inactivo' },
  { label: 'Descripción', value: lab.value.description },
] : []));

async function load() {
  try {
    await fetchLabById(route.params.id);
    await fetchSchedule(route.params.id);
  } catch {
    // El composable deja el motivo en error.value; el panel lo muestra.
  }
}

onMounted(load);
</script>
