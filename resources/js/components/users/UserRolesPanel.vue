<template>
  <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Roles individuales</h2>
    <p class="mb-4 mt-1 text-sm text-gray-600 dark:text-gray-300">Roles adicionales al del grupo, con caducidad opcional.</p>

    <form class="grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_auto_auto]" @submit.prevent="assign">
      <BaseSelect v-model="roleId" name="role_id" label="Rol" placeholder="Selecciona un rol" :options="roleOptions" :error="validationErrors.role_id?.[0]" />
      <BaseInput v-model="expiresAt" name="expires_at" type="datetime-local" label="Caduca (opcional)" :error="validationErrors.expires_at?.[0]" />
      <BaseButton type="submit" :loading="isLoading" :disabled="!roleId">Asignar</BaseButton>
    </form>

    <p v-if="error" class="mt-3 text-sm text-red-600 dark:text-red-400" role="alert">{{ error }}</p>

    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
      <li v-for="assignment in userRoles" :key="assignment.id" class="flex items-center justify-between gap-4 py-2">
        <div>
          <p class="text-sm font-medium text-gray-900 dark:text-white">{{ assignment.role?.display_name ?? `Rol #${assignment.role_id}` }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ assignment.expires_at ? `Caduca ${assignment.expires_at_human}` : 'Sin caducidad' }}
            <span v-if="assignment.is_expired" class="ml-1 text-red-600">(caducado)</span>
          </p>
        </div>
        <BaseButton variant="danger" :loading="revokingId === assignment.id" @click="revoke(assignment)">Revocar</BaseButton>
      </li>
      <li v-if="userRoles.length === 0" class="py-3 text-sm text-gray-500 dark:text-gray-400">Sin roles individuales.</li>
    </ul>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import { useRoles } from '@/composables/useRoles';
import { useToast } from '@/composables/useToast';
import { useUserRoles } from '@/composables/useUserRoles';
import { toApiDateTime } from '@/utils/datetime';

const props = defineProps({ userId: { type: [Number, String], required: true } });
const emit = defineEmits(['changed']);

const toast = useToast();
const { roles, fetchRoles } = useRoles();
const { userRoles, isLoading, error, validationErrors, fetchUserRoles, assignUserRole, revokeUserRole } = useUserRoles();

const roleId = ref('');
const expiresAt = ref('');
const revokingId = ref(null);

const roleOptions = computed(() => roles.value
  .filter((r) => r.is_active)
  .map((r) => ({ value: r.id, text: r.display_name })));

async function assign() {
  const payload = { role_id: Number(roleId.value) };
  if (expiresAt.value) payload.expires_at = toApiDateTime(new Date(expiresAt.value));

  const created = await assignUserRole(props.userId, payload);

  if (created) {
    toast.success('Rol asignado.');
    roleId.value = '';
    expiresAt.value = '';
    await fetchUserRoles(props.userId);
    emit('changed');
  }
}

async function revoke(assignment) {
  revokingId.value = assignment.id;

  try {
    if (await revokeUserRole(props.userId, assignment.id)) {
      toast.success('Rol revocado.');
      await fetchUserRoles(props.userId);
      emit('changed');
    }
  } finally {
    revokingId.value = null;
  }
}

onMounted(async () => {
  await Promise.all([fetchRoles(), fetchUserRoles(props.userId)]);
});
</script>
