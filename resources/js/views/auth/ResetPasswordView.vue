<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nueva contraseña</h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Elige una contraseña de al menos 8 caracteres.</p>
    </div>

    <p v-if="!token" class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" role="alert">
      El enlace no es válido. Solicita uno nuevo desde "Recuperar contraseña".
    </p>

    <form v-else class="space-y-6" @submit.prevent="submit">
      <BaseInput v-model="email" name="email" type="email" label="Correo electrónico" autocomplete="email" :error="errors.email" required />
      <BaseInput v-model="password" name="password" type="password" label="Nueva contraseña" autocomplete="new-password" :error="errors.password" required />
      <BaseInput v-model="passwordConfirmation" name="password_confirmation" type="password" label="Confirmar contraseña" autocomplete="new-password" :error="errors.password_confirmation" required />
      <BaseButton type="submit" :loading="loading" block>Restablecer contraseña</BaseButton>
    </form>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import { rules, useValidatedForm } from '@/composables/useValidatedForm';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''));
const loading = ref(false);

const { defineField, errors, handleSubmit, applyServerErrors } = useValidatedForm(yup.object({
  email: rules.email(),
  password: rules.password(),
  password_confirmation: rules.passwordConfirmation('password'),
}), {
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
  password_confirmation: '',
});

const [email] = defineField('email');
const [password] = defineField('password');
const [passwordConfirmation] = defineField('password_confirmation');

const submit = handleSubmit(async (values) => {
  loading.value = true;

  try {
    const message = await authStore.resetPassword({ ...values, token: token.value });
    toast.success(message ?? 'Contraseña restablecida.');
    router.push('/login');
  } catch (err) {
    if (!applyServerErrors(err)) toast.error('No se pudo restablecer la contraseña.');
  } finally {
    loading.value = false;
  }
});
</script>
