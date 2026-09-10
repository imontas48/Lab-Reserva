<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Usuarios</h1>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Roles base, bloqueos por inasistencia y bajas. Las cuentas se crean desde el registro público.</p>
    </div>

    <div class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
      <BaseInput v-model="search" name="search" label="Buscar" placeholder="Nombre o correo" class="w-64" @update:model-value="debouncedLoad" />
      <BaseSelect v-model="role" name="role" label="Rol" placeholder="Todos" :options="ROLE_OPTIONS" class="w-44" @update:model-value="reload" />
      <label class="flex items-center gap-2 pb-2 text-sm text-gray-700 dark:text-gray-300">
        <input v-model="blocked" type="checkbox" class="h-4 w-4 rounded border-gray-300" @change="reload" />
        Solo bloqueados
      </label>
    </div>

    <DataTable :columns="columns" :items="users" :loading="loading" :error="error" item-key="id">
      <template #cell-name="{ item }">
        <router-link :to="`/users/${item.id}`" class="font-medium text-gray-900 hover:underline dark:text-white">{{ item.name }}</router-link>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.email }}</div>
      </template>
      <template #cell-role="{ value }">
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="ROLE_CLASSES[value]">{{ ROLE_LABELS[value] ?? value }}</span>
      </template>
      <template #cell-no_show_count="{ item }">
        <span class="tabular-nums">{{ item.no_show_count }}</span>
        <span v-if="item.is_blocked" class="ml-2 inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900/30 dark:text-orange-300">Bloqueado</span>
      </template>
      <template #actions="{ item }">
        <router-link :to="`/users/${item.id}`" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">Gestionar</router-link>
      </template>
      <template #empty>
        <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">No hay usuarios con esos filtros.</p>
      </template>
    </DataTable>

    <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-gray-600 dark:text-gray-400">Página {{ meta.current_page }} de {{ meta.last_page }} · {{ meta.total }} usuarios</p>
      <div class="flex gap-2">
        <BaseButton variant="secondary" :disabled="page <= 1" @click="goTo(page - 1)">‹ Anterior</BaseButton>
        <BaseButton variant="secondary" :disabled="page >= meta.last_page" @click="goTo(page + 1)">Siguiente ›</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import DataTable from '@/components/ui/DataTable.vue';
import { useUsers } from '@/composables/useUsers';

const { users, meta, loading, error, fetchUsers } = useUsers();

const ROLE_OPTIONS = [
  { value: 'admin', text: 'Administrador' },
  { value: 'teacher', text: 'Profesor' },
  { value: 'student', text: 'Estudiante' },
];
const ROLE_LABELS = { admin: 'Administrador', teacher: 'Profesor', student: 'Estudiante' };
const ROLE_CLASSES = {
  admin: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
  teacher: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  student: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
};

const columns = [
  { key: 'name', label: 'Usuario' },
  { key: 'role', label: 'Rol' },
  { key: 'reservations_count', label: 'Reservas' },
  { key: 'no_show_count', label: 'Inasistencias' },
];

const search = ref('');
const role = ref('');
const blocked = ref(false);
const page = ref(1);
let debounceTimer = null;

async function load() {
  const params = { page: page.value };
  if (search.value) params.search = search.value;
  if (role.value) params.role = role.value;
  if (blocked.value) params.blocked = 1;

  try {
    await fetchUsers(params);
  } catch {
    /* error.value ya está informado */
  }
}

function reload() {
  page.value = 1;
  load();
}

function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(reload, 400);
}

function goTo(target) {
  page.value = target;
  load();
}

onUnmounted(() => clearTimeout(debounceTimer));
onMounted(load);
</script>
