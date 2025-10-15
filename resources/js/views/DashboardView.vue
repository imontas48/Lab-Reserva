<template>
  <div>
    <!-- Encabezado -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
      <p class="mt-2 text-sm text-gray-600">
        Bienvenido de nuevo, {{ userName }}
      </p>
    </div>

    <!-- Estado de carga -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="text-center">
        <svg
          class="mx-auto h-12 w-12 animate-spin text-blue-600"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <p class="mt-4 text-sm text-gray-500">Cargando datos...</p>
      </div>
    </div>

    <!-- Contenido del dashboard -->
    <div v-else>
      <!-- Grid de estadísticas -->
      <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card: Laboratorios -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg
                  class="h-8 w-8 text-blue-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                  />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="truncate text-sm font-medium text-gray-500">
                    Laboratorios Disponibles
                  </dt>
                  <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ stats.available_labs }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-6 py-3">
            <router-link
              to="/labs"
              class="text-sm font-medium text-blue-600 hover:text-blue-500"
            >
              Ver todos
            </router-link>
          </div>
        </div>

        <!-- Card: Equipos -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg
                  class="h-8 w-8 text-green-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                  />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="truncate text-sm font-medium text-gray-500">
                    Equipos Registrados
                  </dt>
                  <dd class="mt-1 text-3xl font-semibold text-gray-900">
                    {{ stats.total_equipment }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-6 py-3">
            <router-link
              to="/equipment"
              class="text-sm font-medium text-green-600 hover:text-green-500"
            >
              Ver todos
            </router-link>
          </div>
        </div>

        <!-- Card: Mis Reservas -->
      <!-- Card: Mis Reservas -->
      <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg
                class="h-8 w-8 text-purple-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="truncate text-sm font-medium text-gray-500">
                  Mis Reservas Activas
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                  {{ stats.active_reservations }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
          <router-link
            to="/reservations"
            class="text-sm font-medium text-purple-600 hover:text-purple-500"
          >
            Ver todas
          </router-link>
        </div>
      </div>

      <!-- Card: Software -->
      <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg
                class="h-8 w-8 text-orange-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"
                />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="truncate text-sm font-medium text-gray-500">
                  Software Disponible
                </dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">
                  {{ stats.available_software }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
          <router-link
            to="/software"
            class="text-sm font-medium text-orange-600 hover:text-orange-500"
          >
            Ver todo
          </router-link>
        </div>
      </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="mb-8">
      <h2 class="mb-4 text-xl font-semibold text-gray-900">
        Acciones Rápidas
      </h2>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <router-link
          to="/reservations/create"
          class="flex items-center rounded-lg border-2 border-dashed border-gray-300 p-6 transition-colors hover:border-blue-500 hover:bg-blue-50"
        >
          <svg
            class="mr-3 h-8 w-8 text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          <div>
            <h3 class="text-sm font-medium text-gray-900">Nueva Reserva</h3>
            <p class="mt-1 text-xs text-gray-500">
              Reservar un laboratorio
            </p>
          </div>
        </router-link>

        <router-link
          to="/labs"
          class="flex items-center rounded-lg border-2 border-dashed border-gray-300 p-6 transition-colors hover:border-green-500 hover:bg-green-50"
        >
          <svg
            class="mr-3 h-8 w-8 text-green-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          <div>
            <h3 class="text-sm font-medium text-gray-900">Buscar Laboratorio</h3>
            <p class="mt-1 text-xs text-gray-500">
              Explorar laboratorios disponibles
            </p>
          </div>
        </router-link>

        <router-link
          to="/equipment"
          class="flex items-center rounded-lg border-2 border-dashed border-gray-300 p-6 transition-colors hover:border-purple-500 hover:bg-purple-50"
        >
          <svg
            class="mr-3 h-8 w-8 text-purple-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
          <div>
            <h3 class="text-sm font-medium text-gray-900">Ver Equipos</h3>
            <p class="mt-1 text-xs text-gray-500">
              Consultar equipos disponibles
            </p>
          </div>
        </router-link>
      </div>
    </div>

    <!-- Próximas reservas -->
    <div>
      <h2 class="mb-4 text-xl font-semibold text-gray-900">
        Próximas Reservas
      </h2>
      <div class="overflow-hidden rounded-lg bg-white shadow">
        <!-- Sin reservas -->
        <div v-if="upcomingReservations.length === 0" class="p-6">
          <p class="text-center text-sm text-gray-500">
            No tienes reservas próximas.
          </p>
          <div class="mt-4 text-center">
            <router-link
              to="/reservations/create"
              class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
              Crear Nueva Reserva
            </router-link>
          </div>
        </div>

        <!-- Lista de reservas -->
        <div v-else class="divide-y divide-gray-200">
          <div
            v-for="reservation in upcomingReservations"
            :key="reservation.id"
            class="p-6 hover:bg-gray-50"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <h3 class="text-sm font-medium text-gray-900">
                  {{ reservation.lab_name }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  {{ reservation.equipment_name }}
                </p>
                <div class="mt-2 flex items-center text-xs text-gray-500">
                  <svg
                    class="mr-1 h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                  </svg>
                  {{ formatDate(reservation.start_datetime) }}
                  -
                  {{ formatDate(reservation.end_datetime) }}
                </div>
              </div>
              <div>
                <router-link
                  :to="`/reservations/${reservation.id}`"
                  class="text-sm font-medium text-blue-600 hover:text-blue-500"
                >
                  Ver detalles
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/utils/api';

const authStore = useAuthStore();
const userName = computed(() => authStore.userName);

const loading = ref(true);
const stats = ref({
  available_labs: 0,
  total_equipment: 0,
  active_reservations: 0,
  available_software: 0,
});
const upcomingReservations = ref([]);

/**
 * Formatea una fecha para mostrarla de forma legible
 */
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';

  const date = new Date(dateString);
  const options = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  };

  return date.toLocaleDateString('es-ES', options);
};

/**
 * Carga las estadísticas del dashboard
 */
const loadDashboardStats = async () => {
  try {
    loading.value = true;
    const response = await api.get('/dashboard/stats');

    stats.value = response.data.stats;
    upcomingReservations.value = response.data.upcoming_reservations;
  } catch (error) {
    console.error('Error al cargar estadísticas:', error);
  } finally {
    loading.value = false;
  }
};

// Cargar datos al montar el componente
onMounted(() => {
  loadDashboardStats();
});
</script>
