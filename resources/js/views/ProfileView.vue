<template>
  <DetailPanel
    title="Mi Perfil"
    subtitle="Datos de tu cuenta"
    :fields="fields"
  />
</template>

<script setup>
import { computed } from 'vue';
import DetailPanel from '@/components/ui/DetailPanel.vue';
import { useAuthStore } from '@/stores/auth';

/**
 * Los datos ya están en el store tras restaurar la sesión, así que no hace
 * falta ninguna petición adicional.
 */
const authStore = useAuthStore();

const fields = computed(() => [
  { label: 'Nombre', value: authStore.user?.name },
  { label: 'Correo electrónico', value: authStore.user?.email },
  { label: 'Rol', value: authStore.userRoleLabel },
]);
</script>
