<template>
  <div class="mx-auto max-w-2xl px-4 py-8">

    <!-- Breadcrumb -->
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
      <router-link to="/dashboard" class="hover:text-blue-600 dark:hover:text-blue-400">Inicio</router-link>
      <span>/</span>
      <router-link to="/roles" class="hover:text-blue-600 dark:hover:text-blue-400">Roles</router-link>
      <span>/</span>
      <span class="text-gray-900 dark:text-white">{{ isEditing ? 'Editar' : 'Crear' }}</span>
    </nav>

    <h1 class="mb-6 text-3xl font-bold text-gray-900 dark:text-white">
      {{ isEditing ? 'Editar Rol' : 'Crear Nuevo Rol' }}
    </h1>

    <!-- Skeleton carga inicial (modo edición) -->
    <div v-if="initialLoading" class="animate-pulse space-y-4 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
      <div class="h-4 w-1/4 rounded bg-gray-200 dark:bg-gray-700"></div>
      <div class="h-10 rounded bg-gray-200 dark:bg-gray-700"></div>
      <div class="h-4 w-1/4 rounded bg-gray-200 dark:bg-gray-700"></div>
      <div class="h-10 rounded bg-gray-200 dark:bg-gray-700"></div>
    </div>

    <!-- Formulario -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">

      <!-- Slug (solo en creación) -->
      <BaseInput
        v-if="!isEditing"
        v-model="form.name"
        label="Slug (identificador único)"
        placeholder="ej. lab_manager"
        :error="firstError('name')"
        required
      />

      <!-- Nombre para mostrar -->
      <BaseInput
        v-model="form.display_name"
        label="Nombre del rol"
        placeholder="ej. Gestor de Laboratorio"
        :error="firstError('display_name')"
        required
      />

      <!-- Descripción -->
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
          Descripción <span class="text-gray-400 dark:text-gray-500">(opcional)</span>
        </label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Describe el propósito de este rol…"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500"
        ></textarea>
      </div>

      <!-- Color -->
      <BaseSelect
        v-model="form.color"
        label="Color del badge"
        :options="colorOptions"
        :error="firstError('color')"
      />

      <!-- Activo -->
      <div class="flex items-center gap-3">
        <button
          type="button"
          role="switch"
          :aria-checked="form.is_active"
          @click="form.is_active = !form.is_active"
          :class="form.is_active ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
          class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        >
          <span
            :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"
            class="inline-block h-5 w-5 translate-y-0.5 rounded-full bg-white shadow transition-transform"
          ></span>
        </button>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ form.is_active ? 'Rol activo' : 'Rol inactivo' }}
        </span>
      </div>

      <!-- Selección de permisos -->
      <div>
        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
          Permisos asignados
          <span class="ml-1 text-xs text-gray-500">({{ selectedPermissions.length }} seleccionados)</span>
        </p>

        <!-- Carga de permisos -->
        <div v-if="permissionsLoading" class="text-sm text-gray-500 dark:text-gray-400">Cargando permisos…</div>

        <div v-else class="space-y-3 rounded-md border border-gray-200 p-3 dark:border-gray-700">
          <!-- Agrupar por subject -->
          <div v-for="(group, subject) in permissionsBySubject" :key="subject">
            <div class="mb-1.5 flex items-center justify-between">
              <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ subject }}
              </span>
              <button
                type="button"
                @click="toggleSubject(subject)"
                class="text-xs text-blue-600 hover:underline dark:text-blue-400"
              >
                {{ isSubjectFullySelected(subject) ? 'Quitar todos' : 'Seleccionar todos' }}
              </button>
            </div>
            <div class="flex flex-wrap gap-2">
              <label
                v-for="perm in group"
                :key="perm.id"
                :title="perm.description"
                class="flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1 text-xs transition-colors"
                :class="selectedPermissions.includes(perm.id)
                  ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-900/30 dark:text-blue-300'
                  : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300'"
              >
                <input
                  type="checkbox"
                  :value="perm.id"
                  v-model="selectedPermissions"
                  class="sr-only"
                />
                {{ perm.action }}
              </label>
            </div>
          </div>
        </div>
        <p v-if="firstError('permission_ids')" class="mt-1 text-xs text-red-600 dark:text-red-400">
          {{ firstError('permission_ids') }}
        </p>
      </div>

      <!-- Error general -->
      <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
        {{ error }}
      </div>

      <!-- Acciones -->
      <div class="flex justify-end gap-3 pt-2">
        <router-link
          to="/roles"
          class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          Cancelar
        </router-link>
        <button
          type="submit"
          :disabled="isLoading"
          class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60"
        >
          {{ isLoading ? 'Guardando…' : (isEditing ? 'Actualizar rol' : 'Crear rol') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRoles } from '@/composables/useRoles';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';

const route  = useRoute();
const router = useRouter();
const toast  = useToast();

const { role, isLoading, error, validationErrors, fetchRole, createRole, updateRole, syncPermissions } = useRoles();
const { permissions, isLoading: permissionsLoading, fetchPermissions } = usePermissions();

const isEditing     = computed(() => !!route.params.id);
const initialLoading = ref(false);

const form = ref({ name: '', display_name: '', description: '', color: 'blue', is_active: true });
const selectedPermissions = ref([]);

const colorOptions = [
  { value: 'blue',   text: 'Azul'    },
  { value: 'green',  text: 'Verde'   },
  { value: 'red',    text: 'Rojo'    },
  { value: 'yellow', text: 'Amarillo'},
  { value: 'purple', text: 'Morado'  },
  { value: 'orange', text: 'Naranja' },
  { value: 'gray',   text: 'Gris'    },
];

// Agrupa permisos por subject para mostrarlos en la UI
const permissionsBySubject = computed(() => {
  return permissions.value.reduce((groups, perm) => {
    if (!groups[perm.subject]) groups[perm.subject] = [];
    groups[perm.subject].push(perm);
    return groups;
  }, {});
});

function isSubjectFullySelected(subject) {
  return permissionsBySubject.value[subject]?.every(p => selectedPermissions.value.includes(p.id));
}

function toggleSubject(subject) {
  const ids = permissionsBySubject.value[subject]?.map(p => p.id) ?? [];
  if (isSubjectFullySelected(subject)) {
    selectedPermissions.value = selectedPermissions.value.filter(id => !ids.includes(id));
  } else {
    selectedPermissions.value = [...new Set([...selectedPermissions.value, ...ids])];
  }
}

function firstError(field) {
  return validationErrors.value[field]?.[0] ?? null;
}

async function handleSubmit() {
  let saved;
  if (isEditing.value) {
    saved = await updateRole(route.params.id, form.value);
    if (saved) {
      await syncPermissions(saved.id, selectedPermissions.value);
      toast.success('Rol actualizado correctamente.');
      router.push('/roles');
    }
  } else {
    saved = await createRole({ ...form.value, permission_ids: selectedPermissions.value });
    if (saved) {
      toast.success('Rol creado correctamente.');
      router.push('/roles');
    }
  }
  if (!saved) {
    toast.error(error.value ?? 'Por favor, corrige los errores del formulario.');
  }
}

onMounted(async () => {
  await fetchPermissions();
  if (isEditing.value) {
    initialLoading.value = true;
    await fetchRole(route.params.id);
    if (role.value) {
      form.value = {
        name:         role.value.name,
        display_name: role.value.display_name,
        description:  role.value.description ?? '',
        color:        role.value.color,
        is_active:    role.value.is_active,
      };
      selectedPermissions.value = role.value.permissions?.map(p => p.id) ?? [];
    }
    initialLoading.value = false;
  }
});
</script>
