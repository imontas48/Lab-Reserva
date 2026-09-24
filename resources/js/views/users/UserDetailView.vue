<template>
  <div class="space-y-6">
    <DetailPanel title="Usuario" :subtitle="user?.name" :loading="loading && !user" :error="error" :fields="fields" @retry="load">
      <template #actions>
        <div v-if="user" class="flex flex-wrap gap-2">
          <BaseButton v-if="user.is_blocked" variant="secondary" :loading="busy === 'unblock'" @click="handleUnblock">Levantar bloqueo</BaseButton>
          <BaseButton variant="danger" :loading="busy === 'delete'" :disabled="isSelf" @click="handleDelete">Dar de baja</BaseButton>
        </div>
      </template>
    </DetailPanel>

    <template v-if="user">
      <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Rol base</h2>
        <p class="mb-4 mt-1 text-sm text-gray-600 dark:text-gray-300">
          Define los permisos por defecto y quién administra el sistema. No puedes cambiar el tuyo.
        </p>
        <div class="flex flex-wrap items-end gap-3">
          <BaseSelect v-model="roleDraft" name="role" label="Rol" :options="ROLE_OPTIONS" :disabled="isSelf" class="w-56" />
          <BaseButton :loading="busy === 'role'" :disabled="isSelf || roleDraft === user.role" @click="handleRoleChange">Guardar rol</BaseButton>
        </div>
      </section>

      <UserRolesPanel :user-id="user.id" @changed="loadPermissions" />
      <UserOverridesPanel :user-id="user.id" @changed="loadPermissions" />

      <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Permisos efectivos</h2>
        <p class="mb-3 mt-1 text-sm text-gray-600 dark:text-gray-300">Resultado de rol base, roles individuales y sobreescrituras.</p>
        <div class="flex flex-wrap gap-1.5">
          <span v-for="key in effective" :key="key" class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ key }}</span>
          <span v-if="effective.length === 0" class="text-sm text-gray-500">Sin permisos.</span>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import DetailPanel from '@/components/ui/DetailPanel.vue';
import UserOverridesPanel from '@/components/users/UserOverridesPanel.vue';
import UserRolesPanel from '@/components/users/UserRolesPanel.vue';
import { useAuthStore } from '@/stores/auth';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import { useUsers } from '@/composables/useUsers';
import { confirmDestructive } from '@/utils/confirm';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const { user, loading, error, fetchUserById, updateUser, deleteUser, unblockUser } = useUsers();
const { fetchEffectivePermissions } = usePermissions();

const ROLE_OPTIONS = [
  { value: 'admin', text: 'Administrador' },
  { value: 'teacher', text: 'Profesor' },
  { value: 'student', text: 'Estudiante' },
];
const ROLE_LABELS = Object.fromEntries(ROLE_OPTIONS.map((o) => [o.value, o.text]));

const roleDraft = ref('');
const effective = ref([]);
const busy = ref(null);

const isSelf = computed(() => user.value?.id === authStore.user?.id);

const formatDateTime = (value) => (value ? new Intl.DateTimeFormat('es', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(value)) : null);

const fields = computed(() => (user.value ? [
  { label: 'Nombre', value: user.value.name },
  { label: 'Correo', value: user.value.email },
  { label: 'Rol base', value: ROLE_LABELS[user.value.role] ?? user.value.role },
  { label: 'Reservas totales', value: user.value.reservations_count },
  { label: 'Reservas activas', value: user.value.active_reservations_count },
  { label: 'Inasistencias', value: user.value.no_show_count },
  { label: 'Bloqueado hasta', value: formatDateTime(user.value.reservation_blocked_until) },
  { label: 'Alta', value: formatDateTime(user.value.created_at) },
] : []));

watch(user, (value) => { roleDraft.value = value?.role ?? ''; });

async function loadPermissions() {
  const list = await fetchEffectivePermissions(route.params.id);
  effective.value = (list ?? []).map((p) => (typeof p === 'string' ? p : p.key ?? `${p.subject}.${p.action}`));
}

async function load() {
  try {
    await fetchUserById(route.params.id);
    await loadPermissions();
  } catch {
    /* error.value ya está informado */
  }
}

async function handleRoleChange() {
  busy.value = 'role';

  try {
    await updateUser(user.value.id, { role: roleDraft.value });
    toast.success('Rol actualizado.');
    await load();
  } catch (err) {
    toast.error(err?.response?.data?.message ?? 'No se pudo cambiar el rol.');
  } finally {
    busy.value = null;
  }
}

async function handleUnblock() {
  busy.value = 'unblock';

  try {
    await unblockUser(user.value.id);
    toast.success('Bloqueo levantado y contador reiniciado.');
  } catch {
    toast.error('No se pudo levantar el bloqueo.');
  } finally {
    busy.value = null;
  }
}

async function handleDelete() {
  const confirmed = await confirmDestructive({
    title: '¿Dar de baja al usuario?',
    html: `<strong>${user.value.name}</strong> dejará de poder acceder. Su historial de reservas se conserva.`,
    confirmText: 'Sí, dar de baja',
  });

  if (!confirmed) return;

  busy.value = 'delete';

  try {
    await deleteUser(user.value.id);
    toast.success('Usuario dado de baja.');
    router.push('/users');
  } catch (err) {
    toast.error(err?.response?.data?.message ?? 'No se pudo dar de baja al usuario.');
  } finally {
    busy.value = null;
  }
}

onMounted(load);
</script>
