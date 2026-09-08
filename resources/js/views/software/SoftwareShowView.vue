<template>
  <DetailPanel
    title="Detalle de Software"
    :subtitle="softwareItem?.name"
    :loading="loading"
    :error="error"
    :fields="fields"
    @retry="load"
  >
    <template #actions>
      <BaseButton v-if="authStore.isAdmin" variant="secondary" @click="router.push(`/software/${route.params.id}/edit`)">
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
import { useSoftware } from '@/composables/useSoftware';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { softwareItem, loading, error, fetchSoftwareById } = useSoftware();

const fields = computed(() => (softwareItem.value ? [
  { label: 'Nombre', value: softwareItem.value.name },
  { label: 'Versión', value: softwareItem.value.version },
  { label: 'Equipos con este software', value: softwareItem.value.equipment_count },
] : []));

async function load() {
  try {
    await fetchSoftwareById(route.params.id);
  } catch {
    /* el panel muestra error.value */
  }
}

onMounted(load);
</script>
