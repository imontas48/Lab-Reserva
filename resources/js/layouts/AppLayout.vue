<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Navbar -->
    <nav class="border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <!-- Logo y navegación principal -->
          <div class="flex">
            <!-- Logo -->
            <div class="flex flex-shrink-0 items-center">
              <router-link
                to="/dashboard"
                class="text-xl font-bold text-gray-900 dark:text-white"
              >
                <span class="text-blue-600 dark:text-blue-400">Lab</span>-Reserva
              </router-link>
            </div>

            <!-- Links de navegación -->
            <div class="hidden space-x-8 sm:ml-10 sm:flex">
              <router-link
                to="/dashboard"
                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-900 hover:border-blue-500 dark:text-gray-100"
                active-class="border-blue-500"
              >
                Dashboard
              </router-link>
              <router-link
                to="/labs"
                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:text-gray-200"
                active-class="border-blue-500 !text-gray-900 dark:!text-white"
              >
                Laboratorios
              </router-link>
              <router-link
                to="/equipment"
                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:text-gray-200"
                active-class="border-blue-500 !text-gray-900 dark:!text-white"
              >
                Equipos
              </router-link>

              <!-- Menú Reservas -->
              <div
                class="relative flex h-full items-stretch"
                v-click-outside="() => reservationsMenuOpen = false"
              >
                <button
                  @click="reservationsMenuOpen = !reservationsMenuOpen"
                  class="inline-flex items-center gap-1 border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:text-gray-200 focus:outline-none"
                  :class="{ 'border-blue-500 !text-gray-900 dark:!text-white': isReservationsRoute }"
                >
                  Reservas
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Dropdown Reservas -->
                <div
                  v-if="reservationsMenuOpen"
                  class="absolute left-0 top-full z-20 mt-1 w-56 rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                  <router-link
                    to="/reservations"
                    @click="reservationsMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Mis Reservas
                  </router-link>

                  <template v-if="authStore.isAdmin">
                    <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                    <router-link
                      to="/reservations/students"
                      @click="reservationsMenuOpen = false"
                      class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                      <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                      </svg>
                      Reservas de Estudiantes
                    </router-link>
                    <router-link
                      to="/reservations/teachers"
                      @click="reservationsMenuOpen = false"
                      class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                      <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                      Reservas de Maestros
                    </router-link>
                  </template>
                </div>
              </div>

              <!-- Menú Administración (solo admin) -->
              <div
                v-if="authStore.isAdmin"
                class="relative flex h-full items-stretch"
                v-click-outside="() => adminMenuOpen = false"
              >
                <button
                  @click="adminMenuOpen = !adminMenuOpen"
                  class="inline-flex items-center gap-1 border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:text-gray-200 focus:outline-none"
                  :class="{ 'border-blue-500 !text-gray-900 dark:!text-white': isAdminRoute }"
                >
                  Administración
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Dropdown -->
                <div
                  v-if="adminMenuOpen"
                  class="absolute left-0 top-full z-20 mt-1 w-52 rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                  <router-link
                    to="/roles"
                    @click="adminMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Roles
                  </router-link>
                  <router-link
                    to="/permissions"
                    @click="adminMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    Permisos del Sistema
                  </router-link>
                  <router-link
                    to="/group-role-assignments"
                    @click="adminMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Reglas de Grupo
                  </router-link>
                </div>
              </div>
            </div>
          </div>

          <!-- Menú de usuario y tema -->
          <div class="flex items-center gap-4">
            <!-- Toggle de tema -->
            <ThemeToggle variant="dropdown" />

            <div class="flex-shrink-0 flex items-center gap-2">
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ userName }}</span>
              <span
                v-if="userRoleLabel"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200"
              >
                {{ userRoleLabel }}
              </span>
            </div>
            <div class="flex-shrink-0">
              <button
                @click="handleLogout"
                class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
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
import { ref, computed } from 'vue';
import { vClickOutside } from '@/directives/clickOutside';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import ThemeToggle from '@/components/ui/ThemeToggle.vue';

const router = useRouter();
const route  = useRoute();
const authStore = useAuthStore();
const toast = useToast();

const adminMenuOpen = ref(false);
const reservationsMenuOpen = ref(false);

const userName = computed(() => authStore.userName || 'Usuario');
const userRoleLabel = computed(() => authStore.userRoleLabel);
const isAdminRoute  = computed(() => ['/roles', '/permissions', '/group-role-assignments'].some(p => route.path.startsWith(p)));
const isReservationsRoute = computed(() => route.path.startsWith('/reservations'));


const handleLogout = async () => {
  try {
    await authStore.logout();
    toast.info('Sesión cerrada exitosamente');
    router.push('/login');
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
    toast.error('Error al cerrar sesión. Por favor, intenta nuevamente.');
  }
};
</script>
