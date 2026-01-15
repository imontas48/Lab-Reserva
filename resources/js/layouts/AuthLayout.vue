<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 transition-colors duration-200">
    <!-- Contenedor principal centrado -->
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
      <!-- Toggle de tema en esquina superior derecha -->
      <div class="absolute right-4 top-4">
        <ThemeToggle variant="toggle" />
      </div>

      <!-- Logo / Branding -->
      <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
          <span class="text-blue-600 dark:text-blue-400">Lab</span>-Reserva
        </h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Sistema de Gestión de Laboratorios
        </p>
      </div>

      <!-- Contenido de autenticación (Login o Register) -->
      <div class="w-full max-w-md">
        <router-view />
      </div>

      <!-- Footer -->
      <footer class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
        <p>&copy; {{ currentYear }} Lab-Reserva. Todos los derechos reservados.</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ThemeToggle from '@/components/ui/ThemeToggle.vue';

const currentYear = computed(() => new Date().getFullYear());
</script>
