<template>
  <div class="space-y-6">
    <div>
      <nav class="mb-2 text-sm text-gray-500 dark:text-gray-400">
        <router-link to="/labs" class="hover:underline">Laboratorios</router-link>
        <span class="mx-1">/</span>
        <router-link :to="`/labs/${labId}`" class="hover:underline">{{ lab?.name ?? '…' }}</router-link>
        <span class="mx-1">/</span>
        <span class="text-gray-900 dark:text-white">Plano</span>
      </nav>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Plano de {{ lab?.name ?? 'laboratorio' }}</h1>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
        Define la cuadrícula, elige un equipo de la lista y pulsa la celda donde está. Pulsa un equipo colocado para quitarlo.
      </p>
    </div>

    <div v-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" role="alert">{{ error }}</div>

    <template v-if="lab">
      <div class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <BaseInput v-model.number="draft.grid_rows" name="grid_rows" type="number" label="Filas" class="w-28" />
        <BaseInput v-model.number="draft.grid_cols" name="grid_cols" type="number" label="Columnas" class="w-28" />
        <BaseButton :loading="loading" @click="save">Guardar plano</BaseButton>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-[16rem_1fr]">
        <aside class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
          <h2 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Equipos sin colocar ({{ unplaced.length }})</h2>
          <ul class="space-y-1">
            <li v-for="item in unplaced" :key="item.id">
              <button
                type="button"
                class="w-full rounded-md border px-3 py-1.5 text-left text-sm"
                :class="selectedId === item.id
                  ? 'border-blue-500 bg-blue-50 text-blue-900 dark:bg-blue-900/30 dark:text-blue-100'
                  : 'border-gray-200 text-gray-800 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700'"
                @click="selectedId = selectedId === item.id ? null : item.id"
              >
                {{ item.identifier }}
              </button>
            </li>
            <li v-if="unplaced.length === 0" class="text-sm text-gray-500 dark:text-gray-400">Todos los equipos están colocados.</li>
          </ul>
        </aside>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <LabGrid :lab="draftLab" :equipment="draftEquipment" selectable @select="handleCell" />
        </section>
      </div>
    </template>

    <div v-else-if="loading" class="flex justify-center py-12"><BaseSpinner size="lg" class="text-blue-600" /></div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import LabGrid from '@/components/labs/LabGrid.vue';
import { useLabMap } from '@/composables/useLabMap';
import { useToast } from '@/composables/useToast';
import { buildGrid } from '@/utils/labGrid';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { lab, equipment, loading, error, fetchMap, saveLayout } = useLabMap();

const labId = computed(() => Number(route.params.id));
const draft = reactive({ grid_rows: 1, grid_cols: 1 });
const positions = ref({});
const selectedId = ref(null);

const draftLab = computed(() => ({ grid_rows: draft.grid_rows, grid_cols: draft.grid_cols }));
const draftEquipment = computed(() => equipment.value.map((item) => ({
  ...item,
  grid_row: positions.value[item.id]?.row ?? null,
  grid_col: positions.value[item.id]?.col ?? null,
})));
const unplaced = computed(() => buildGrid(draftLab.value, draftEquipment.value).unplaced);

watch(lab, (value) => {
  if (!value) return;
  draft.grid_rows = value.grid_rows || 1;
  draft.grid_cols = value.grid_cols || 1;
  positions.value = Object.fromEntries(equipment.value
    .filter((e) => e.grid_row && e.grid_col)
    .map((e) => [e.id, { row: e.grid_row, col: e.grid_col }]));
});

function handleCell(cell, { row, col }) {
  if (cell) {
    // Pulsar un equipo colocado lo devuelve a la lista.
    const next = { ...positions.value };
    delete next[cell.id];
    positions.value = next;

    return;
  }

  if (selectedId.value === null) {
    toast.info('Elige primero un equipo de la lista.');

    return;
  }

  positions.value = { ...positions.value, [selectedId.value]: { row, col } };
  selectedId.value = null;
}

async function save() {
  try {
    await saveLayout(labId.value, {
      grid_rows: draft.grid_rows,
      grid_cols: draft.grid_cols,
      positions: Object.entries(positions.value).map(([id, pos]) => ({ equipment_id: Number(id), ...pos })),
    });
    toast.success('Plano guardado.');
    router.push(`/labs/${labId.value}/map`);
  } catch (err) {
    const errors = err?.response?.data?.errors;
    toast.error(errors ? Object.values(errors).flat()[0] : (error.value ?? 'No se pudo guardar el plano.'));
  }
}

onMounted(() => fetchMap(labId.value).catch(() => {}));
</script>
