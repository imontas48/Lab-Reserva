<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Recuperar contraseña</h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Te enviaremos un enlace para restablecerla.</p>
    </div>

    <p v-if="sentMessage" class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-300" role="status">
      {{ sentMessage }}
    </p>

    <form class="space-y-6" @submit.prevent="submit">
      <BaseInput v-model="email" name="email" type="email" label="Correo electrónico" autocomplete="email" :error="errors.email" required />
      <BaseButton type="submit" :loading="loading" block>Enviar enlace</BaseButton>
      <div class="text-center text-sm">
        <router-link to="/login" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">Volver a iniciar sesión</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import { rules, useValidatedForm } from '@/composables/useValidatedForm';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const loading = ref(false);
const sentMessage = ref('');

const { defineField, errors, handleSubmit, applyServerErrors } = useValidatedForm(yup.object({ email: rules.email() }), { email: '' });
const [email] = defineField('email');

const submit = handleSubmit(async (values) => {
  loading.value = true;
  sentMessage.value = '';

  try {
    sentMessage.value = await authStore.forgotPassword(values.email);
  } catch (err) {
    if (!applyServerErrors(err)) {
      sentMessage.value = err?.response?.status === 429
        ? 'Demasiados intentos. Espera un minuto y vuelve a probar.'
        : 'No se pudo enviar el enlace. Inténtalo de nuevo.';
    }
  } finally {
    loading.value = false;
  }
});
</script>
