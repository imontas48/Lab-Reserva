<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <nav class="mb-2 text-sm text-gray-500 dark:text-gray-400">
          <router-link to="/labs" class="hover:underline">Laboratorios</router-link>
          <span class="mx-1">/</span>
          <router-link :to="`/labs/${labId}`" class="hover:underline">{{ lab?.name ?? '…' }}</router-link>
          <span class="mx-1">/</span>
          <span class="text-gray-900 dark:text-white">Mapa</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mapa de {{ lab?.name ?? 'laboratorio' }}</h1>
        <p v-if="generatedAt" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Estado en tiempo real · actualizado {{ formatTime(generatedAt) }} · se refresca cada 30 s
        </p>
      </div>
      <div class="flex gap-2">
        <BaseButton variant="secondary" :loading="loading" @click="refresh">Actualizar</BaseButton>
        <BaseButton v-if="authStore.isAdmin" variant="secondary" @click="router.push(`/labs/${labId}/layout`)">Editar plano</BaseButton>
      </div>
    </div>

    <div v-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" role="alert">{{ error }}</div>

    <template v-if="lab">
      <div class="flex flex-wrap gap-4 text-sm">
        <span v-for="(style, key) in STATUS_STYLES" :key="key" class="inline-flex items-center gap-2">
          <span class="inline-block h-4 w-4 rounded border-2" :class="style.classes"></span>
          {{ style.label }} <span class="text-gray-500">({{ counts[key] }})</span>
        </span>
      </div>

      <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" :class="{ 'opacity-60': loading }">
        <p v-if="!lab.grid_rows || !lab.grid_cols" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Este laboratorio aún no tiene plano. {{ authStore.isAdmin ? 'Defínelo en "Editar plano".' : '' }}
        </p>
        <LabGrid v-else :lab="lab" :equipment="equipment" @select="openEquipment" />
      </section>

      <section v-if="unplaced.length > 0" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Equipos sin posición en el plano</h2>
        <div class="flex flex-wrap gap-2">
          <router-link
            v-for="item in unplaced"
            :key="item.id"
            :to="`/equipment/${item.id}`"
            class="rounded-md border-2 px-3 py-1.5 text-xs font-medium"
            :class="STATUS_STYLES[item.status?.status]?.classes"
          >
            {{ item.identifier }}
          </router-link>
        </div>
      </section>
    </template>

    <div v-else-if="loading" class="flex justify-center py-12"><BaseSpinner size="lg" class="text-blue-600" /></div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import LabGrid from '@/components/labs/LabGrid.vue';
import { useAuthStore } from '@/stores/auth';
import { useLabMap } from '@/composables/useLabMap';
import { STATUS_STYLES, buildGrid, countByStatus } from '@/utils/labGrid';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { lab, equipment, generatedAt, loading, error, fetchMap } = useLabMap();

const labId = computed(() => Number(route.params.id));
const counts = computed(() => countByStatus(equipment.value));
const unplaced = computed(() => (lab.value ? buildGrid(lab.value, equipment.value).unplaced : []));

const formatTime = (value) => new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(new Date(value));

let timer = null;

async function refresh() {
  try {
    await fetchMap(labId.value);
  } catch {
    /* error.value ya está informado */
  }
}

function openEquipment(cell) {
  if (cell) router.push(`/equipment/${cell.id}`);
}

onMounted(() => {
  refresh();
  timer = setInterval(refresh, 30000);
});

onUnmounted(() => clearInterval(timer));
</script>
