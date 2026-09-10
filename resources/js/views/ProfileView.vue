<template>
  <div class="space-y-6">
    <DetailPanel title="Mi Perfil" subtitle="Datos de tu cuenta" :fields="fields" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Datos personales</h2>
        <form class="space-y-4" @submit.prevent="submitProfile">
          <BaseInput v-model="name" name="name" label="Nombre" :error="profileErrors.name" required />
          <BaseInput v-model="email" name="email" type="email" label="Correo electrónico" autocomplete="email" :error="profileErrors.email" required />
          <div class="flex justify-end">
            <BaseButton type="submit" :loading="savingProfile">Guardar cambios</BaseButton>
          </div>
        </form>
      </section>

      <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">Cambiar contraseña</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">Al cambiarla se cerrarán tus sesiones en otros dispositivos.</p>
        <form class="space-y-4" @submit.prevent="submitPassword">
          <BaseInput v-model="currentPassword" name="current_password" type="password" label="Contraseña actual" autocomplete="current-password" :error="passwordErrors.current_password" required />
          <BaseInput v-model="newPassword" name="password" type="password" label="Nueva contraseña" autocomplete="new-password" :error="passwordErrors.password" required />
          <BaseInput v-model="newPasswordConfirmation" name="password_confirmation" type="password" label="Confirmar nueva contraseña" autocomplete="new-password" :error="passwordErrors.password_confirmation" required />
          <div class="flex justify-end">
            <BaseButton type="submit" :loading="savingPassword">Actualizar contraseña</BaseButton>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import DetailPanel from '@/components/ui/DetailPanel.vue';
import { rules, useValidatedForm } from '@/composables/useValidatedForm';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const toast = useToast();

const fields = computed(() => [
  { label: 'Nombre', value: authStore.user?.name },
  { label: 'Correo electrónico', value: authStore.user?.email },
  { label: 'Rol', value: authStore.userRoleLabel },
  { label: 'Inasistencias registradas', value: authStore.user?.no_show_count ?? 0 },
]);

const savingProfile = ref(false);
const savingPassword = ref(false);

const profileForm = useValidatedForm(yup.object({
  name: rules.requiredText('El nombre'),
  email: rules.email(),
}), { name: authStore.user?.name ?? '', email: authStore.user?.email ?? '' });
const [name] = profileForm.defineField('name');
const [email] = profileForm.defineField('email');
const profileErrors = profileForm.errors;

const passwordForm = useValidatedForm(yup.object({
  current_password: yup.string().required('Indica tu contraseña actual.'),
  password: rules.password(),
  password_confirmation: rules.passwordConfirmation('password'),
}), { current_password: '', password: '', password_confirmation: '' });
const [currentPassword] = passwordForm.defineField('current_password');
const [newPassword] = passwordForm.defineField('password');
const [newPasswordConfirmation] = passwordForm.defineField('password_confirmation');
const passwordErrors = passwordForm.errors;

const submitProfile = profileForm.handleSubmit(async (values) => {
  savingProfile.value = true;

  try {
    await authStore.updateProfile(values);
    toast.success('Perfil actualizado.');
  } catch (err) {
    if (!profileForm.applyServerErrors(err)) toast.error('No se pudo guardar el perfil.');
  } finally {
    savingProfile.value = false;
  }
});

const submitPassword = passwordForm.handleSubmit(async (values) => {
  savingPassword.value = true;

  try {
    const message = await authStore.changePassword(values);
    toast.success(message ?? 'Contraseña actualizada.');
    passwordForm.resetForm({ values: { current_password: '', password: '', password_confirmation: '' } });
  } catch (err) {
    if (!passwordForm.applyServerErrors(err)) toast.error('No se pudo cambiar la contraseña.');
  } finally {
    savingPassword.value = false;
  }
});
</script>
