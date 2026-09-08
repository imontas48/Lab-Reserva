<template>
  <DetailPanel
    title="Detalle de Equipo"
    :subtitle="equipmentItem?.identifier"
    :loading="loading"
    :error="error"
    :fields="fields"
    @retry="load"
  >
    <template #actions>
      <BaseButton v-if="authStore.isAdmin" variant="secondary" @click="router.push(`/equipment/${route.params.id}/edit`)">
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
import { useEquipment } from '@/composables/useEquipment';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { equipmentItem, loading, error, fetchEquipmentById } = useEquipment();

const fields = computed(() => (equipmentItem.value ? [
  { label: 'Identificador', value: equipmentItem.value.identifier },
  { label: 'Laboratorio', value: equipmentItem.value.lab?.name },
  { label: 'Tipo', value: equipmentItem.value.type },
  { label: 'Estado', value: equipmentItem.value.status?.details },
  { label: 'Operativo', value: equipmentItem.value.is_operational ? 'Sí' : 'No' },
  { label: 'Especificaciones', value: equipmentItem.value.specifications },
] : []));

async function load() {
  try {
    await fetchEquipmentById(route.params.id);
  } catch {
    /* el panel muestra error.value */
  }
}

onMounted(load);
</script>
