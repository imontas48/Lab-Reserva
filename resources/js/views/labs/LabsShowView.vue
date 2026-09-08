<template>
  <DetailPanel
    title="Detalle de Laboratorio"
    :subtitle="lab?.name"
    :loading="loading"
    :error="error"
    :fields="fields"
    @retry="load"
  >
    <template #actions>
      <BaseButton v-if="authStore.isAdmin" variant="secondary" @click="router.push(`/labs/${route.params.id}/edit`)">
        Editar
      </BaseButton>
    </template>
  </DetailPanel>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import DetailPanel from '@/components/ui/DetailPanel.vue';
import { useLabs } from '@/composables/useLabs';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { lab, loading, error, fetchLabById } = useLabs();

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
  } catch {
    // El composable deja el motivo en error.value; el panel lo muestra.
  }
}

onMounted(load);
</script>
