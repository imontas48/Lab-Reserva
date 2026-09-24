<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notificaciones</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Avisos sobre tus reservas y solicitudes.</p>
      </div>
      <BaseButton v-if="unreadCount > 0" variant="secondary" @click="handleMarkAll">Marcar todas como leídas</BaseButton>
    </div>

    <div v-if="loading && notifications.length === 0" class="flex justify-center py-12">
      <BaseSpinner size="lg" class="text-blue-600" />
    </div>

    <div v-else-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" role="alert">
      {{ error }}
      <button class="ml-2 font-medium underline" @click="load">Reintentar</button>
    </div>

    <p v-else-if="notifications.length === 0" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
      No tienes notificaciones.
    </p>

    <ul v-else class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800">
      <li
        v-for="notification in notifications"
        :key="notification.id"
        class="flex items-start justify-between gap-4 px-6 py-4"
        :class="{ 'bg-blue-50/60 dark:bg-blue-900/20': !notification.is_read }"
      >
        <div>
          <p class="font-medium text-gray-900 dark:text-white">{{ notification.title }}</p>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ notification.message }}</p>
          <p class="mt-1 text-xs text-gray-400">{{ formatDate(notification.created_at) }}</p>
        </div>
        <div class="flex shrink-0 flex-col items-end gap-2">
          <router-link v-if="notification.url" :to="notification.url" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
            Abrir
          </router-link>
          <button v-if="!notification.is_read" type="button" class="text-xs text-gray-500 hover:underline" @click="markAsRead(notification.id)">
            Marcar como leída
          </button>
        </div>
      </li>
    </ul>

    <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-gray-600 dark:text-gray-400">Página {{ meta.current_page }} de {{ meta.last_page }}</p>
      <div class="flex gap-2">
        <BaseButton variant="secondary" :disabled="page <= 1" @click="goTo(page - 1)">‹ Anterior</BaseButton>
        <BaseButton variant="secondary" :disabled="page >= meta.last_page" @click="goTo(page + 1)">Siguiente ›</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import { useNotifications } from '@/composables/useNotifications';

const {
  notifications, meta, unreadCount, loading, error, fetchNotifications, fetchUnreadCount, markAsRead, markAllAsRead,
} = useNotifications();

const page = ref(1);

const formatDate = (value) => new Intl.DateTimeFormat('es-ES', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));

async function load() {
  try {
    await fetchNotifications({ page: page.value });
    await fetchUnreadCount();
  } catch {
    /* error.value ya está informado */
  }
}

function goTo(target) {
  page.value = target;
  load();
}

async function handleMarkAll() {
  await markAllAsRead();
}

onMounted(load);
</script>
