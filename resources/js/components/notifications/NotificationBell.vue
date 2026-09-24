<template>
  <div class="relative" v-click-outside="() => open = false">
    <button
      type="button"
      class="relative rounded-full p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
      :aria-label="`Notificaciones${unreadCount ? `, ${unreadCount} sin leer` : ''}`"
      @click="toggle"
    >
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <span
        v-if="unreadCount > 0"
        class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <div
      v-if="open"
      class="absolute right-0 top-full z-30 mt-2 w-80 rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2 dark:border-gray-700">
        <span class="text-sm font-semibold text-gray-900 dark:text-white">Notificaciones</span>
        <button
          v-if="unreadCount > 0"
          type="button"
          class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
          @click="markAllAsRead"
        >
          Marcar todas como leídas
        </button>
      </div>

      <div v-if="loading && notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">Cargando…</div>
      <p v-else-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
        No tienes notificaciones.
      </p>
      <ul v-else class="max-h-96 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700">
        <li v-for="notification in notifications" :key="notification.id">
          <button
            type="button"
            class="block w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700"
            :class="{ 'bg-blue-50/60 dark:bg-blue-900/20': !notification.is_read }"
            @click="openNotification(notification)"
          >
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ notification.title }}</p>
            <p class="mt-0.5 line-clamp-2 text-xs text-gray-600 dark:text-gray-300">{{ notification.message }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ relativeTime(notification.created_at) }}</p>
          </button>
        </li>
      </ul>

      <router-link
        to="/notifications"
        class="block border-t border-gray-100 px-4 py-2 text-center text-xs font-medium text-blue-600 hover:underline dark:border-gray-700 dark:text-blue-400"
        @click="open = false"
      >
        Ver todas
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { vClickOutside } from '@/directives/clickOutside';
import { useNotifications } from '@/composables/useNotifications';

/**
 * Campana de la barra de navegación: contador de no leídas por sondeo y
 * lista rápida de las últimas notificaciones.
 */
const router = useRouter();
const {
  notifications, unreadCount, loading, fetchNotifications, markAsRead, markAllAsRead, startPolling,
} = useNotifications();

const open = ref(false);

async function toggle() {
  open.value = !open.value;

  if (open.value) {
    try {
      await fetchNotifications({ per_page: 10 });
    } catch {
      /* la lista queda vacía y el usuario puede ir a "Ver todas" */
    }
  }
}

async function openNotification(notification) {
  if (!notification.is_read) {
    try {
      await markAsRead(notification.id);
    } catch {
      /* no bloquea la navegación */
    }
  }

  open.value = false;

  if (notification.url) {
    router.push(notification.url);
  }
}

const relativeTime = (value) => {
  const minutes = Math.round((Date.now() - new Date(value)) / 60000);

  if (minutes < 1) return 'ahora mismo';
  if (minutes < 60) return `hace ${minutes} min`;

  const hours = Math.round(minutes / 60);
  if (hours < 24) return `hace ${hours} h`;

  return new Date(value).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
};

onMounted(() => startPolling(60000));
</script>
