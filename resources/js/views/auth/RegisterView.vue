<template>
  <div class="rounded-lg bg-white px-8 py-8 shadow-xl dark:bg-gray-800">
    <h2 class="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">
      Crear Cuenta
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
      <!-- Nombre -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Nombre Completo
        </label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          required
          autocomplete="name"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
          :class="{ 'border-red-500 dark:border-red-400': errors.name }"
          @input="clearError('name')"
        />
        <p v-if="errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.name[0] }}
        </p>
      </div>

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
          autocomplete="new-password"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
          :class="{ 'border-red-500 dark:border-red-400': errors.password }"
          @input="clearError('password')"
        />
        <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.password[0] }}
        </p>
      </div>

      <!-- Password Confirmation -->
      <div>
        <label
          for="password_confirmation"
          class="block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Confirmar Contraseña
        </label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          autocomplete="new-password"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
        />
      </div>

      <!-- Botón de submit -->
      <div>
        <button
          type="submit"
          :disabled="loading"
          class="flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
        >
          <span v-if="loading">Creando cuenta...</span>
          <span v-else>Crear Cuenta</span>
        </button>
      </div>

      <!-- Link a login -->
      <div class="text-center text-sm">
        <span class="text-gray-600 dark:text-gray-400">¿Ya tienes cuenta?</span>
        <router-link
          to="/login"
          class="ml-1 font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Inicia sesión aquí
        </router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

// Formulario reactivo
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
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
    await authStore.register(form);

    // Notificación de éxito
    toast.success(`¡Registro exitoso! Bienvenido, ${authStore.userName}`);

    // Redirigir al dashboard
    router.push('/dashboard');
  } catch (error) {
    // Los errores ya están manejados en el store
    console.error('Error en registro:', error);

    // Mostrar notificación de error
    if (error.response?.status === 422) {
      toast.error('Por favor, corrige los errores en el formulario.');
    } else {
      toast.error('Error al registrar la cuenta. Por favor, intenta nuevamente.');
    }
  }
};
</script>
