<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Crear Cuenta</h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Regístrate para reservar equipos de laboratorio
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
        v-model="name"
        name="name"
        label="Nombre Completo"
        autocomplete="name"
        :error="errors.name"
        required
      />

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
        autocomplete="new-password"
        :error="errors.password"
        required
      />

      <BaseInput
        v-model="passwordConfirmation"
        name="password_confirmation"
        type="password"
        label="Confirmar Contraseña"
        autocomplete="new-password"
        :error="errors.password_confirmation"
        required
      />

      <BaseButton type="submit" :loading="authStore.loading" block>
        {{ authStore.loading ? 'Creando cuenta...' : 'Crear Cuenta' }}
      </BaseButton>

      <div class="text-center text-sm">
        <span class="text-gray-600 dark:text-gray-400">¿Ya tienes cuenta?</span>
        <router-link
          to="/login"
          class="ml-1 font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Inicia sesión
        </router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import { rules, useValidatedForm } from '@/composables/useValidatedForm';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

/**
 * El campo de confirmación no tenía NINGUNA comprobación: ni de coincidencia ni
 * de presentación de su error. Se enviaba una contraseña mal confirmada y solo
 * el backend la rechazaba, con el formulario ya vaciado.
 *
 * El mínimo de 8 caracteres espeja la regla del servidor
 * (AuthController::register).
 */
const schema = yup.object({
  name: rules.requiredText('El nombre'),
  email: rules.email(),
  password: rules.password(),
  password_confirmation: rules.passwordConfirmation('password'),
});

const { defineField, errors, handleSubmit, applyServerErrors } = useValidatedForm(schema, {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const [name] = defineField('name');
const [email] = defineField('email');
const [password] = defineField('password');
const [passwordConfirmation] = defineField('password_confirmation');

const generalError = computed(() => authStore.errors.general?.[0] ?? null);

const submit = handleSubmit(async (values) => {
  try {
    await authStore.register(values);

    toast.success(`¡Registro exitoso! Bienvenido, ${authStore.userName}`);
    await router.push('/dashboard');
  } catch (error) {
    applyServerErrors(error);
  }
});
</script>
