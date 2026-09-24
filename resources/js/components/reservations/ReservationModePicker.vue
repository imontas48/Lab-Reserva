<template>
  <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h2 class="mb-2 flex items-center text-xl font-semibold text-gray-900 dark:text-white">
      <span class="mr-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-600">2</span>
      ¿Qué quieres reservar en {{ lab.name }}?
    </h2>
    <p class="mb-6 ml-11 text-sm text-gray-600 dark:text-gray-300">
      Un equipo para uso individual, o el laboratorio completo para impartir una clase.
    </p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <button
        v-for="option in options"
        :key="option.mode"
        type="button"
        class="group flex flex-col rounded-lg border-2 border-gray-200 bg-white p-6 text-left transition-all hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700"
        :class="option.classes"
        @click="$emit('select', option.mode)"
      >
        <span class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg" :class="option.iconClasses">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="option.icon"/>
          </svg>
        </span>
        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ option.title }}</span>
        <span class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ option.description }}</span>
        <span v-if="option.note" class="mt-3 inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
          {{ option.note }}
        </span>
      </button>
    </div>

    <BaseButton variant="ghost" class="mt-6" @click="$emit('back')">
      ← Cambiar laboratorio
    </BaseButton>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';

/**
 * Paso intermedio del asistente, solo para quien puede apartar un
 * laboratorio completo (reservations.createLab): equipo individual o clase.
 */
const props = defineProps({
  lab: { type: Object, required: true },
  /** Si se aprueba automáticamente (admin), la solicitud no queda pendiente. */
  autoApproved: { type: Boolean, default: false },
});

defineEmits(['select', 'back']);

const options = computed(() => [
  {
    mode: 'equipment',
    title: 'Un equipo',
    description: 'Reserva un puesto concreto del laboratorio para uso individual.',
    icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    classes: 'hover:border-blue-500 focus:ring-blue-500',
    iconClasses: 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300',
    note: null,
  },
  {
    mode: 'lab',
    title: 'El laboratorio completo',
    description: 'Bloquea todos los equipos en la franja elegida para impartir una clase.',
    icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    classes: 'hover:border-purple-500 focus:ring-purple-500',
    iconClasses: 'bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-300',
    note: props.autoApproved ? null : 'Requiere aprobación del administrador',
  },
]);
</script>
