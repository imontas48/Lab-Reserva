<template>
  <div class="min-h-screen">
    <!-- Navbar -->
    <nav class="border-b border-gray-200 bg-white shadow-sm">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <!-- Logo y navegación principal -->
          <div class="flex">
            <!-- Logo -->
            <div class="flex flex-shrink-0 items-center">
              <router-link
                to="/dashboard"
                class="text-xl font-bold text-gray-900"
              >
                <span class="text-blue-600">Lab</span>-Reserva
              </router-link>
            </div>

            <!-- Links de navegación -->
            <div class="hidden space-x-8 sm:ml-10 sm:flex">
              <router-link
                to="/dashboard"
                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-900 hover:border-blue-500"
                active-class="border-blue-500"
              >
                Dashboard
              </router-link>
              <router-link
                to="/labs"
                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                active-class="border-blue-500 !text-gray-900"
              >
                Laboratorios
              </router-link>
              <router-link
                to="/equipment"
                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                active-class="border-blue-500 !text-gray-900"
              >
                Equipos
              </router-link>
              <router-link
                to="/reservations"
                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                active-class="border-blue-500 !text-gray-900"
              >
                Mis Reservas
              </router-link>
            </div>
          </div>

          <!-- Menú de usuario -->
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <span class="text-sm text-gray-700">{{ userName }}</span>
            </div>
            <div class="ml-4 flex-shrink-0">
              <button
                @click="handleLogout"
                class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                Cerrar Sesión
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenido principal -->
    <main class="py-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const userName = computed(() => authStore.userName || 'Usuario');

const handleLogout = async () => {
  try {
    await authStore.logout();

    // Notificación de éxito
    toast.info('Sesión cerrada exitosamente');

    router.push('/login');
  } catch (error) {
    console.error('Error al cerrar sesión:', error);

    // Notificación de error
    toast.error('Error al cerrar sesión. Por favor, intenta nuevamente.');
  }
};
</script>
