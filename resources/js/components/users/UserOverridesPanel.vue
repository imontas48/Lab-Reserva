<template>
  <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sobreescrituras de permisos</h2>
    <p class="mb-4 mt-1 text-sm text-gray-600 dark:text-gray-300">Conceden o revocan un permiso concreto por encima de los roles. Una revocación siempre gana.</p>

    <form class="grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_auto_1fr_auto]" @submit.prevent="create">
      <BaseSelect v-model="permissionId" name="permission_id" label="Permiso" placeholder="Selecciona un permiso" :options="permissionOptions" :error="validationErrors.permission_id?.[0]" />
      <BaseSelect v-model="type" name="type" label="Tipo" :options="TYPE_OPTIONS" :error="validationErrors.type?.[0]" />
      <BaseInput v-model="reason" name="reason" label="Motivo" placeholder="Por qué se concede o revoca" :error="validationErrors.reason?.[0]" />
      <BaseButton type="submit" :loading="isLoading" :disabled="!permissionId">Añadir</BaseButton>
    </form>

    <p v-if="error" class="mt-3 text-sm text-red-600 dark:text-red-400" role="alert">{{ error }}</p>

    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
      <li v-for="override in overrides" :key="override.id" class="flex items-center justify-between gap-4 py-2">
        <div>
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            <span class="font-mono text-xs">{{ override.permission_key ?? override.permission?.key ?? `#${override.permission_id}` }}</span>
            <span
              class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
              :class="override.type === 'grant'
                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'"
            >
              {{ override.type_label ?? (override.type === 'grant' ? 'Concedido' : 'Revocado') }}
            </span>
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ override.reason }}</p>
        </div>
        <BaseButton variant="danger" :loading="deletingId === override.id" @click="remove(override)">Quitar</BaseButton>
      </li>
      <li v-if="overrides.length === 0" class="py-3 text-sm text-gray-500 dark:text-gray-400">Sin sobreescrituras.</li>
    </ul>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';

const props = defineProps({ userId: { type: [Number, String], required: true } });
const emit = defineEmits(['changed']);

const toast = useToast();
const {
  permissions, isLoading, error, validationErrors, fetchPermissions, fetchOverrides, createOverride, deleteOverride,
} = usePermissions();

const TYPE_OPTIONS = [
  { value: 'grant', text: 'Conceder' },
  { value: 'revoke', text: 'Revocar' },
];

const overrides = ref([]);
const permissionId = ref('');
const type = ref('grant');
const reason = ref('');
const deletingId = ref(null);

const permissionOptions = computed(() => permissions.value.map((p) => ({
  value: p.id,
  text: `${p.key ?? `${p.subject}.${p.action}`} — ${p.description ?? ''}`,
})));

async function loadOverrides() {
  overrides.value = await fetchOverrides(props.userId);
}

async function create() {
  const created = await createOverride(props.userId, {
    permission_id: Number(permissionId.value),
    type: type.value,
    reason: reason.value || null,
  });

  if (created) {
    toast.success('Sobreescritura añadida.');
    permissionId.value = '';
    reason.value = '';
    await loadOverrides();
    emit('changed');
  }
}

async function remove(override) {
  deletingId.value = override.id;

  try {
    if (await deleteOverride(props.userId, override.id)) {
      toast.success('Sobreescritura eliminada.');
      await loadOverrides();
      emit('changed');
    }
  } finally {
    deletingId.value = null;
  }
}

onMounted(async () => {
  await Promise.all([fetchPermissions(), loadOverrides()]);
});
</script>
