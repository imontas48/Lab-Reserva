<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 dark:bg-gray-900 sm:px-6 lg:px-8">
    <div class="w-full max-w-md text-center">
      <h1 class="text-9xl font-bold text-gray-900 dark:text-white">404</h1>
      <h2 class="mt-4 text-3xl font-bold text-gray-900 dark:text-white">
        Página no encontrada
      </h2>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Lo sentimos, no pudimos encontrar la página que estás buscando.
      </p>
      <div class="mt-6">
        <router-link
          to="/dashboard"
          class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          Volver al Dashboard
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
// Componente simple, no necesita lógica adicional
</script>
