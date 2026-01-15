<template>
  <div class="rounded-lg bg-white px-8 py-8 shadow-xl dark:bg-gray-800">
    <h2 class="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">
      Iniciar Sesión
    </h2>

    <!-- Mensaje de error general -->
    <div
      v-if="errors.general"
      class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400"
    >
      {{ errors.general[0] }}
    </div>

    <!-- Formulario -->
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Correo Electrónico
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
          :class="{ 'border-red-500 dark:border-red-400': errors.email }"
          @input="clearError('email')"
        />
        <p v-if="errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.email[0] }}
        </p>
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Contraseña
        </label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
          :class="{ 'border-red-500 dark:border-red-400': errors.password }"
          @input="clearError('password')"
        />
        <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.password[0] }}
        </p>
      </div>

      <!-- Remember me -->
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
          />
          <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
            Recordarme
          </label>
        </div>
      </div>

      <!-- Botón de submit -->
      <div>
        <button
          type="submit"
          :disabled="loading"
          class="flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
        >
          <span v-if="loading">Iniciando sesión...</span>
          <span v-else>Iniciar Sesión</span>
        </button>
      </div>

      <!-- Link a registro -->
      <div class="text-center text-sm">
        <span class="text-gray-600 dark:text-gray-400">¿No tienes cuenta?</span>
        <router-link
          to="/register"
          class="ml-1 font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Regístrate aquí
        </router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const toast = useToast();

// Formulario reactivo
const form = reactive({
  email: '',
  password: '',
  remember: false,
});

// Computed properties
const loading = computed(() => authStore.loading);
const errors = computed(() => authStore.errors);

// Limpiar error específico cuando el usuario escribe
const clearError = (field) => {
  if (authStore.errors[field]) {
    delete authStore.errors[field];
  }
};

// Manejar el submit del formulario
const handleSubmit = async () => {
  try {
    await authStore.login(form);

    // Notificación de éxito
    toast.success(`¡Bienvenido, ${authStore.userName}!`);

    // Redirigir al dashboard o a la ruta que intentaba acceder
    const redirect = route.query.redirect || '/dashboard';
    router.push(redirect);
  } catch (error) {
    // Los errores ya están manejados en el store
    console.error('Error en login:', error);

    // Mostrar notificación de error
    if (error.response?.status === 422) {
      toast.error('Credenciales incorrectas. Por favor, verifica tus datos.');
    } else {
      toast.error('Error al iniciar sesión. Por favor, intenta nuevamente.');
    }
  }
};
</script>
