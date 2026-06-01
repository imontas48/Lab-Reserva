<template>
  <div>
    <!-- Encabezado -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Permisos del Sistema</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Catálogo de permisos registrados, agrupados por recurso
        </p>
      </div>
    </div>

    <!-- Estado de carga -->
    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 4" :key="i" class="animate-pulse rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-3 h-4 w-1/5 rounded bg-gray-200 dark:bg-gray-700"></div>
        <div class="flex flex-wrap gap-2">
          <div v-for="j in 4" :key="j" class="h-7 w-24 rounded-full bg-gray-200 dark:bg-gray-700"></div>
        </div>
      </div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="rounded-lg border border-red-200 bg-red-50 px-6 py-8 text-center dark:border-red-800 dark:bg-red-900/20"
    >
      <p class="text-sm text-red-700 dark:text-red-400">{{ error }}</p>
    </div>

    <!-- Sin permisos -->
    <div
      v-else-if="!permissions.length"
      class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-800"
    >
      <p class="text-sm text-gray-500 dark:text-gray-400">No hay permisos registrados.</p>
    </div>

    <!-- Grupos por subject -->
    <div v-else class="space-y-5">
      <div
        v-for="(group, subject) in permissionsBySubject"
        :key="subject"
        class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <!-- Cabecera del grupo -->
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-base font-semibold capitalize text-gray-900 dark:text-white">
            {{ subject }}
          </h2>
          <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ group.length }} {{ group.length === 1 ? 'permiso' : 'permisos' }}
          </span>
        </div>

        <!-- Badges de acciones -->
        <div class="flex flex-wrap gap-2">
          <span
            v-for="perm in group"
            :key="perm.id"
            :title="perm.description ?? perm.key"
            class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
          >
            {{ perm.action }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

const { permissions, isLoading, error, fetchPermissions } = usePermissions();

const permissionsBySubject = computed(() => {
  return permissions.value.reduce((groups, perm) => {
    if (!groups[perm.subject]) groups[perm.subject] = [];
    groups[perm.subject].push(perm);
    return groups;
  }, {});
});

onMounted(() => fetchPermissions());
</script>
