<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Elige tu contraseña</h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Entraste con una contraseña temporal. Para seguir, {{ authStore.userName }}, define una definitiva.
      </p>
    </div>

    <form class="space-y-6" @submit.prevent="submit">
      <BaseInput
        v-model="currentPassword"
        name="current_password"
        type="password"
        label="Contraseña temporal"
        autocomplete="current-password"
        :error="errors.current_password"
        required
      />

      <BaseInput
        v-model="newPassword"
        name="password"
        type="password"
        label="Nueva contraseña"
        autocomplete="new-password"
        :error="errors.password"
        required
      />

      <BaseInput
        v-model="newPasswordConfirmation"
        name="password_confirmation"
        type="password"
        label="Confirmar nueva contraseña"
        autocomplete="new-password"
        :error="errors.password_confirmation"
        required
      />

      <BaseButton type="submit" :loading="saving" block>
        {{ saving ? 'Guardando...' : 'Guardar y continuar' }}
      </BaseButton>

      <div class="text-center text-sm">
        <button type="button" class="font-medium text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300" @click="leave">
          Cerrar sesión
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import { rules, useValidatedForm } from '@/composables/useValidatedForm';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';

/**
 * Cambio de contraseña obligatorio tras entrar con una temporal.
 *
 * El guard del router trae aquí a todo usuario con must_change_password y no
 * le deja ir a ninguna otra ruta; el backend, además, rechaza con 403 cualquier
 * llamada que no sea esta. La pantalla vive en AuthLayout para que no haya
 * menú ni enlaces a secciones que de todos modos no podría usar.
 */
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const saving = ref(false);

const schema = yup.object({
  current_password: yup.string().required('Indica la contraseña temporal.'),
  password: rules.password()
    .notOneOf([yup.ref('current_password')], 'La nueva contraseña debe ser distinta de la temporal.'),
  password_confirmation: rules.passwordConfirmation('password'),
});

const { defineField, errors, handleSubmit, applyServerErrors } = useValidatedForm(schema, {
  current_password: '',
  password: '',
  password_confirmation: '',
});

const [currentPassword] = defineField('current_password');
const [newPassword] = defineField('password');
const [newPasswordConfirmation] = defineField('password_confirmation');

const submit = handleSubmit(async (values) => {
  saving.value = true;

  try {
    await authStore.changePassword(values);
    toast.success('Contraseña guardada. ¡Bienvenido!');
    await router.push({ name: 'dashboard' });
  } catch (err) {
    if (!applyServerErrors(err)) toast.error('No se pudo guardar la contraseña.');
  } finally {
    saving.value = false;
  }
});

async function leave() {
  await authStore.logout();
  router.push({ name: 'login' });
}
</script>
