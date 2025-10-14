<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <!-- Contenedor principal centrado -->
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
      <!-- Logo / Branding -->
      <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900">
          <span class="text-blue-600">Lab</span>-Reserva
        </h1>
        <p class="mt-2 text-sm text-gray-600">
          Sistema de Gestión de Laboratorios
        </p>
      </div>

      <!-- Contenido de autenticación (Login o Register) -->
      <div class="w-full max-w-md">
        <router-view />
      </div>

      <!-- Footer -->
      <footer class="mt-8 text-center text-sm text-gray-500">
        <p>&copy; {{ currentYear }} Lab-Reserva. Todos los derechos reservados.</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const currentYear = computed(() => new Date().getFullYear());
</script>
