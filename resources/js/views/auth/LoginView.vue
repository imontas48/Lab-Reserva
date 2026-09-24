<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Iniciar Sesión</h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Accede a tu cuenta de Lab-Reserva
      </p>
    </div>

    <p
      v-if="generalError"
      class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300"
      role="alert"
    >
      {{ generalError }}
    </p>

    <form class="space-y-6" @submit.prevent="submit">
      <BaseInput
        v-model="email"
        name="email"
        type="email"
        label="Correo Electrónico"
        autocomplete="email"
        :error="errors.email"
        required
      />

      <BaseInput
        v-model="password"
        name="password"
        type="password"
        label="Contraseña"
        autocomplete="current-password"
        :error="errors.password"
        required
      />

      <BaseButton type="submit" :loading="authStore.loading" block>
        {{ authStore.loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
      </BaseButton>

      <div class="text-center text-sm">
        <router-link to="/forgot-password" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
          ¿Olvidaste tu contraseña?
        </router-link>
      </div>

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
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import { rules, useValidatedForm } from '@/composables/useValidatedForm';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const toast = useToast();

/**
 * El formulario no tenía ninguna validación de cliente: solo `required` nativo
 * y los errores del 422. Ahora el schema atrapa el correo mal formado y la
 * contraseña corta sin salir a la red.
 */
const schema = yup.object({
  email: rules.email(),
  password: yup.string().required('La contraseña es obligatoria.'),
});

const { defineField, errors, handleSubmit, applyServerErrors } = useValidatedForm(schema, {
  email: '',
  password: '',
});

const [email] = defineField('email');
const [password] = defineField('password');

/**
 * Errores que no pertenecen a ningún campo: red caída, 429, credenciales.
 */
const generalError = computed(() => authStore.errors.general?.[0] ?? null);

const submit = handleSubmit(async (values) => {
  try {
    await authStore.login(values);

    // Contraseña temporal: el guard lo forzaría igual, pero así el mensaje
    // y el destino son coherentes desde el primer momento.
    if (authStore.mustChangePassword) {
      toast.info('Elige tu contraseña definitiva para continuar.');
      await router.push({ name: 'password.change' });

      return;
    }

    toast.success(`¡Bienvenido, ${authStore.userName}!`);

    // Vuelve a donde el guard interrumpió, si venía de una ruta protegida.
    const target = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard';
    await router.push(target);
  } catch (error) {
    // El servidor sigue siendo la autoridad: sus errores se pintan sobre los
    // mismos campos que los del schema.
    applyServerErrors(error);
  }
});
</script>
