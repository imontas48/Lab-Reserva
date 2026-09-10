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

                  <router-link
                    v-if="authStore.canApproveReservations"
                    to="/reservations/pending"
                    @click="reservationsMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Solicitudes pendientes
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
                    to="/users"
                    @click="adminMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Usuarios
                  </router-link>
                  <router-link
                    to="/reports"
                    @click="adminMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Reportes
                  </router-link>
                  <router-link
                    to="/incidents"
                    @click="adminMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Incidencias
                  </router-link>
                  <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
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
                  <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                  <router-link
                    to="/closures"
                    @click="adminMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Cierres y festivos
                  </router-link>
                  <router-link
                    to="/academic-periods"
                    @click="adminMenuOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Periodos académicos
                  </router-link>
                </div>
              </div>
            </div>
          </div>

          <!-- Menú de usuario y tema -->
          <div class="flex items-center gap-4">
            <NotificationBell />

            <!-- Toggle de tema -->
            <ThemeToggle variant="dropdown" />

            <router-link to="/profile" class="hidden flex-shrink-0 items-center gap-2 rounded-md px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 sm:flex" title="Mi perfil">
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ userName }}</span>
              <span
                v-if="userRoleLabel"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200"
              >
                {{ userRoleLabel }}
              </span>
            </router-link>
            <div class="hidden flex-shrink-0 sm:block">
              <button
                @click="handleLogout"
                class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
              >
                Cerrar Sesión
              </button>
            </div>

            <!-- Hamburguesa (solo móvil) -->
            <button
              type="button"
              class="rounded-md p-2 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-300 dark:hover:bg-gray-700 sm:hidden"
              :aria-expanded="mobileMenuOpen"
              aria-controls="mobile-menu"
              aria-label="Abrir menú"
              @click="mobileMenuOpen = !mobileMenuOpen"
            >
              <svg v-if="!mobileMenuOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
              <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Menú móvil: mismo contenido que la barra, apilado -->
      <div v-if="mobileMenuOpen" id="mobile-menu" class="border-t border-gray-200 sm:hidden dark:border-gray-700">
        <nav class="space-y-1 px-4 py-3" aria-label="Menú principal">
          <router-link
            v-for="link in mobileLinks"
            :key="link.to"
            :to="link.to"
            class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700"
            active-class="bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200"
            @click="mobileMenuOpen = false"
          >
            {{ link.label }}
          </router-link>
          <div class="my-2 border-t border-gray-100 dark:border-gray-700"></div>
          <router-link to="/profile" class="block rounded-md px-3 py-2 text-sm text-gray-700 dark:text-gray-200" @click="mobileMenuOpen = false">
            {{ userName }} · {{ userRoleLabel }}
          </router-link>
          <button type="button" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20" @click="handleLogout">
            Cerrar sesión
          </button>
        </nav>
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
import { ref, computed, watch } from 'vue';
import { vClickOutside } from '@/directives/clickOutside';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import ThemeToggle from '@/components/ui/ThemeToggle.vue';
import NotificationBell from '@/components/notifications/NotificationBell.vue';

const router = useRouter();
const route  = useRoute();
const authStore = useAuthStore();
const toast = useToast();

const adminMenuOpen = ref(false);
const reservationsMenuOpen = ref(false);
const mobileMenuOpen = ref(false);

// Enlaces del menú móvil, según permisos: mismo contenido que la barra.
const mobileLinks = computed(() => {
  const links = [
    { to: '/dashboard', label: 'Dashboard' },
    { to: '/labs', label: 'Laboratorios' },
    { to: '/equipment', label: 'Equipos' },
    { to: '/reservations', label: 'Mis Reservas' },
    { to: '/reservations/create', label: 'Nueva reserva' },
    { to: '/notifications', label: 'Notificaciones' },
  ];

  if (authStore.canApproveReservations) links.push({ to: '/reservations/pending', label: 'Solicitudes pendientes' });

  if (authStore.isAdmin) {
    links.push(
      { to: '/reservations/students', label: 'Reservas de Estudiantes' },
      { to: '/reservations/teachers', label: 'Reservas de Maestros' },
      { to: '/users', label: 'Usuarios' },
      { to: '/reports', label: 'Reportes' },
      { to: '/incidents', label: 'Incidencias' },
      { to: '/closures', label: 'Cierres y festivos' },
      { to: '/academic-periods', label: 'Periodos académicos' },
      { to: '/roles', label: 'Roles' },
    );
  }

  return links;
});

// Al navegar, el menú móvil se cierra aunque el enlace no pase por @click.
watch(() => route.path, () => { mobileMenuOpen.value = false; });

const userName = computed(() => authStore.userName || 'Usuario');
const userRoleLabel = computed(() => authStore.userRoleLabel);
const isAdminRoute  = computed(() => ['/roles', '/permissions', '/group-role-assignments', '/closures', '/academic-periods', '/users', '/reports', '/incidents'].some(p => route.path.startsWith(p)));
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
