<template>
  <BaseModal :model-value="modelValue" :title="created ? 'Usuario creado' : 'Nuevo usuario'" :close-on-backdrop="!saving" @update:model-value="close">
    <template v-if="!created">
      <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        La cuenta nace con una contraseña temporal que el usuario deberá cambiar en su primer inicio de sesión.
      </p>
      <form id="user-create-form" class="space-y-4" @submit.prevent="submit">
        <BaseInput v-model="name" name="name" label="Nombre" :error="errors.name" required />
        <BaseInput v-model="email" name="email" type="email" label="Correo electrónico" autocomplete="off" :error="errors.email" required />
        <BaseSelect v-model="role" name="role" label="Rol" :options="ROLE_OPTIONS" :error="errors.role" required />
        <BaseInput v-model="password" name="password" type="text" label="Contraseña temporal (opcional)" placeholder="Vacío: se genera una automáticamente" autocomplete="off" :error="errors.password" />
      </form>
    </template>

    <template v-else>
      <p class="text-sm text-gray-600 dark:text-gray-300">
        <strong>{{ created.user.name }}</strong> ({{ created.user.email }}) ya puede entrar con esta contraseña temporal. Anótala ahora: no se volverá a mostrar.
      </p>
      <div class="mt-4 flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
        <code class="flex-1 select-all break-all font-mono text-base text-gray-900 dark:text-white">{{ created.temporaryPassword }}</code>
        <BaseButton variant="secondary" @click="copyPassword">{{ copied ? 'Copiado' : 'Copiar' }}</BaseButton>
      </div>
    </template>

    <template #footer>
      <template v-if="!created">
        <BaseButton variant="secondary" :disabled="saving" @click="close">Cancelar</BaseButton>
        <BaseButton type="submit" form="user-create-form" :loading="saving">Crear usuario</BaseButton>
      </template>
      <BaseButton v-else @click="close">Cerrar</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import * as yup from 'yup';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import { rules, useValidatedForm } from '@/composables/useValidatedForm';
import { useToast } from '@/composables/useToast';
import { useUsers } from '@/composables/useUsers';

/**
 * Alta de usuario desde el panel de administración.
 *
 * Dos estados en un mismo diálogo: el formulario y, tras crear, la
 * contraseña temporal. Se muestra aquí y no en un toast porque el
 * administrador tiene que copiarla con calma; el servidor no la repite.
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits(['update:modelValue', 'created']);

const toast = useToast();
const { createUser } = useUsers();

const ROLE_OPTIONS = [
  { value: 'student', text: 'Estudiante' },
  { value: 'teacher', text: 'Profesor' },
  { value: 'admin', text: 'Administrador' },
];

const EMPTY = { name: '', email: '', role: 'student', password: '' };

const saving = ref(false);
const created = ref(null);
const copied = ref(false);

const schema = yup.object({
  name: rules.requiredText('El nombre'),
  email: rules.email(),
  role: yup.string().required('Elige un rol.').oneOf(ROLE_OPTIONS.map((o) => o.value)),
  password: yup.string().transform((v) => (v === '' ? undefined : v)).min(8, 'La contraseña debe tener al menos 8 caracteres.'),
});

const { defineField, errors, handleSubmit, applyServerErrors, resetForm } = useValidatedForm(schema, { ...EMPTY });
const [name] = defineField('name');
const [email] = defineField('email');
const [role] = defineField('role');
const [password] = defineField('password');

watch(() => props.modelValue, (open) => {
  if (open) {
    created.value = null;
    copied.value = false;
    resetForm({ values: { ...EMPTY } });
  }
});

const submit = handleSubmit(async (values) => {
  saving.value = true;

  try {
    const payload = { ...values };
    if (!payload.password) delete payload.password;

    created.value = await createUser(payload);
    emit('created', created.value);
  } catch (err) {
    if (!applyServerErrors(err)) toast.error(err?.response?.data?.message ?? 'No se pudo crear el usuario.');
  } finally {
    saving.value = false;
  }
});

async function copyPassword() {
  try {
    await navigator.clipboard.writeText(created.value.temporaryPassword);
    copied.value = true;
  } catch {
    toast.warning('No se pudo copiar. Selecciona la contraseña y cópiala a mano.');
  }
}

function close() {
  if (saving.value) return;
  emit('update:modelValue', false);
}
</script>
