<template>
  <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Selección rápida de fecha y hora</h3>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha</label>
        <VueDatePicker
          v-model="date"
          :dark="isDark"
          :min-date="today"
          locale="es"
          format="dd/MM/yyyy"
          :enable-time-picker="false"
          clearable
          auto-apply
          placeholder="dd/mm/aaaa"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Hora de inicio</label>
        <VueDatePicker
          v-model="startTime"
          :dark="isDark"
          time-picker
          clearable
          :minutes-increment="15"
          :min-time="{ hours: 7, minutes: 0 }"
          :max-time="{ hours: 21, minutes: 30 }"
          placeholder="HH:mm"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Hora de fin</label>
        <VueDatePicker
          v-model="endTime"
          :dark="isDark"
          time-picker
          clearable
          :minutes-increment="15"
          :min-time="{ hours: 7, minutes: 30 }"
          :max-time="{ hours: 22, minutes: 0 }"
          placeholder="HH:mm"
        />
      </div>
    </div>

    <p v-if="validationError" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
      {{ validationError }}
    </p>
    <p
      v-else-if="preview"
      class="mt-3 rounded-lg border border-purple-200 bg-purple-50 px-4 py-2.5 text-sm text-purple-800 dark:border-purple-800 dark:bg-purple-900/30 dark:text-purple-300"
    >
      {{ preview }}
    </p>

    <BaseButton class="mt-4" :disabled="!isComplete || !!validationError" @click="submit">
      Confirmar este horario
    </BaseButton>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useThemeStore } from '@/stores/theme';
import { toApiDateTime, toLocalDateString } from '@/utils/datetime';

/**
 * Formulario rápido de franja: fecha + hora inicio + hora fin.
 *
 * Los límites de duración llegan por props porque dependen del rol del
 * usuario (config/lab-reserva.php); el servidor los vuelve a aplicar.
 */
const props = defineProps({
  maxHours: { type: Number, default: 8 },
  minMinutes: { type: Number, default: 30 },
});

const emit = defineEmits(['slot-selected']);

const { isDark } = storeToRefs(useThemeStore());

const date = ref(null);
const startTime = ref(null);
const endTime = ref(null);
const today = new Date();

const isComplete = computed(() => !!(date.value && startTime.value && endTime.value));

const timeToString = (t) => `${String(t.hours).padStart(2, '0')}:${String(t.minutes).padStart(2, '0')}`;

const range = computed(() => {
  if (!isComplete.value) {
    return null;
  }

  const day = date.value instanceof Date ? toLocalDateString(date.value) : date.value;

  return {
    start: new Date(`${day}T${timeToString(startTime.value)}`),
    end: new Date(`${day}T${timeToString(endTime.value)}`),
  };
});

const validationError = computed(() => {
  if (!range.value) return null;

  const { start, end } = range.value;
  if (start < new Date()) return 'La fecha y hora de inicio no puede estar en el pasado.';
  if (end <= start) return 'La hora de fin debe ser posterior a la hora de inicio.';

  const minutes = (end - start) / 60000;
  if (minutes < props.minMinutes) return `La reserva debe tener al menos ${props.minMinutes} minutos de duración.`;
  if (minutes > props.maxHours * 60) return `La reserva no puede superar las ${props.maxHours} horas para tu rol.`;

  return null;
});

const preview = computed(() => {
  if (!range.value) return '';

  const dateLabel = new Intl.DateTimeFormat('es-ES', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
  }).format(range.value.start);

  return `${dateLabel} · ${timeToString(startTime.value)} – ${timeToString(endTime.value)} hrs`;
});

function submit() {
  if (!range.value || validationError.value) return;

  // toApiDateTime deja explícito que lo que viaja es un instante con offset,
  // el mismo formato que emite el calendario.
  emit('slot-selected', {
    start: toApiDateTime(range.value.start),
    end: toApiDateTime(range.value.end),
  });
}
</script>

<style scoped>
/* VueDatePicker: integración con el tema de la app */
:deep(.dp__theme_light) {
  --dp-background-color: #ffffff;
  --dp-text-color: #111827;
  --dp-hover-color: #f3f4f6;
  --dp-hover-text-color: #111827;
  --dp-hover-icon-color: #6b7280;
  --dp-primary-color: #7c3aed;
  --dp-primary-text-color: #ffffff;
  --dp-secondary-color: #e5e7eb;
  --dp-border-color: #d1d5db;
  --dp-menu-border-color: #d1d5db;
  --dp-border-color-hover: #9ca3af;
  --dp-border-color-focus: #7c3aed;
  --dp-disabled-color: #f3f4f6;
  --dp-scroll-bar-background: #f3f4f6;
  --dp-scroll-bar-color: #d1d5db;
  --dp-success-color: #16a34a;
  --dp-success-color-disabled: #bbf7d0;
  --dp-icon-color: #6b7280;
  --dp-danger-color: #dc2626;
  --dp-highlight-color: #ede9fe;
  --dp-font-size: 0.875rem;
  --dp-border-radius: 0.5rem;
}

:deep(.dp__theme_dark) {
  --dp-background-color: #111827;
  --dp-text-color: #f9fafb;
  --dp-hover-color: #1f2937;
  --dp-hover-text-color: #f9fafb;
  --dp-hover-icon-color: #9ca3af;
  --dp-primary-color: #8b5cf6;
  --dp-primary-text-color: #ffffff;
  --dp-secondary-color: #374151;
  --dp-border-color: #4b5563;
  --dp-menu-border-color: #374151;
  --dp-border-color-hover: #6b7280;
  --dp-border-color-focus: #8b5cf6;
  --dp-disabled-color: #1f2937;
  --dp-scroll-bar-background: #1f2937;
  --dp-scroll-bar-color: #374151;
  --dp-success-color: #22c55e;
  --dp-success-color-disabled: #166534;
  --dp-icon-color: #9ca3af;
  --dp-danger-color: #ef4444;
  --dp-highlight-color: #4c1d95;
  --dp-font-size: 0.875rem;
  --dp-border-radius: 0.5rem;
}

:deep(.dp__input) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

:deep(.dp__input:focus) {
  outline: none;
}
</style>
