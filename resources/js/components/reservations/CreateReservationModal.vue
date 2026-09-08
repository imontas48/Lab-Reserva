<template>
  <!-- Transition para aparición suave del modal -->
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <!-- Overlay con backdrop blur -->
      <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="show"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 backdrop-blur-sm transition-opacity dark:bg-gray-900 dark:bg-opacity-80"
            @click="closeModal"
          ></div>
        </Transition>

        <!-- Trick para centrado vertical -->
        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            v-if="show"
            class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
          >
            <!-- Header del Modal -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white bg-opacity-20">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <h3 id="modal-title" class="ml-4 text-xl font-semibold text-white">
                    Confirmar Reserva
                  </h3>
                </div>
                <button
                  @click="closeModal"
                  class="rounded-full p-1 text-white hover:bg-white hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                  :disabled="loading"
                >
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Body del Modal -->
            <div class="bg-white px-6 py-5 dark:bg-gray-800">
              <!-- Mensaje de introducción -->
              <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
                Estás a punto de reservar el siguiente equipo. Por favor, verifica los detalles antes de confirmar.
              </p>

              <!-- Tarjeta con detalles de la reserva -->
              <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-gray-600 dark:bg-gray-700">
                <!-- Equipo -->
                <div class="flex items-start">
                  <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/50">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Equipo</p>
                    <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ equipmentName }}</p>
                  </div>
                </div>

                <!-- Separador -->
                <div class="border-t border-gray-200 dark:border-gray-600"></div>

                <!-- Inicio -->
                <div class="flex items-start">
                  <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/50">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Inicio</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ formattedStartDate }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ formattedStartTime }}</p>
                  </div>
                </div>

                <!-- Separador -->
                <div class="border-t border-gray-200 dark:border-gray-600"></div>

                <!-- Fin -->
                <div class="flex items-start">
                  <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/50">
                    <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Fin</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ formattedEndDate }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ formattedEndTime }}</p>
                  </div>
                </div>

                <!-- Separador -->
                <div class="border-t border-gray-200 dark:border-gray-600"></div>

                <!-- Duración -->
                <div class="flex items-start">
                  <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/50">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Duración</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ durationText }}</p>
                  </div>
                </div>
              </div>

              <!-- Sección de Errores (solo si hay errores) -->
              <div
                v-if="hasValidationErrors"
                class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/30"
              >
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-300">
                      No se pudo confirmar la reserva
                    </h3>
                    <div class="mt-2 text-sm text-red-700 dark:text-red-400">
                      <p v-if="generalError">{{ generalError }}</p>
                      <ul v-else class="list-disc space-y-1 pl-5">
                        <li v-for="(errors, field) in validationErrors" :key="field">
                          {{ Array.isArray(errors) ? errors[0] : errors }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer del Modal (Acciones) -->
            <div class="bg-gray-50 px-6 py-4 dark:bg-gray-700 sm:flex sm:flex-row-reverse sm:gap-3">
              <!-- Botón Confirmar -->
              <button
                @click="handleConfirm"
                :disabled="loading"
                class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm transition-all hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:text-sm"
              >
                <!-- Spinner de loading -->
                <svg
                  v-if="loading"
                  class="mr-2 h-5 w-5 animate-spin text-white"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ loading ? 'Confirmando...' : 'Confirmar Reserva' }}
              </button>

              <!-- Botón Cancelar -->
              <button
                @click="closeModal"
                :disabled="loading"
                class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm"
              >
                Cancelar
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useReservations } from '@/composables/useReservations';

// ============================================================================
// PROPS Y EMITS
// ============================================================================

const props = defineProps({
  /**
   * Controla la visibilidad del modal
   * Se usa con v-model:show
   */
  show: {
    type: Boolean,
    default: false
  },

  /**
   * Detalles esenciales de la reserva a confirmar
   * Estructura: { equipmentId: Number, start: String (ISO 8601), end: String (ISO 8601) }
   */
  reservationDetails: {
    type: Object,
    required: true,
    validator: (value) => {
      return (
        value &&
        typeof value.equipmentId === 'number' &&
        typeof value.start === 'string' &&
        typeof value.end === 'string'
      );
    }
  },

  /**
   * Nombre del equipo para mostrar al usuario
   */
  equipmentName: {
    type: String,
    required: true
  }
});

const emit = defineEmits([
  /**
   * Evento para v-model del show
   */
  'update:show',

  /**
   * Evento emitido cuando la reserva se crea exitosamente
   * El componente padre puede usarlo para recargar el calendario
   */
  'reservation-success'
]);

// ============================================================================
// COMPOSABLES
// ============================================================================

const {
  createReservation,
  loading,
  error,
  validationErrors
} = useReservations();

// ============================================================================
// COMPUTED PROPERTIES
// ============================================================================

/**
 * Formatea la fecha de inicio en un formato legible
 * Ejemplo: "14 de Octubre de 2025"
 */
const formattedStartDate = computed(() => {
  if (!props.reservationDetails?.start) return '';

  const date = new Date(props.reservationDetails.start);
  return date.toLocaleDateString('es-ES', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
});

/**
 * Formatea la hora de inicio
 * Ejemplo: "14:00"
 */
const formattedStartTime = computed(() => {
  if (!props.reservationDetails?.start) return '';

  const date = new Date(props.reservationDetails.start);
  return date.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  });
});

/**
 * Formatea la fecha de fin en un formato legible
 * Ejemplo: "14 de Octubre de 2025"
 */
const formattedEndDate = computed(() => {
  if (!props.reservationDetails?.end) return '';

  const date = new Date(props.reservationDetails.end);
  return date.toLocaleDateString('es-ES', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
});

/**
 * Formatea la hora de fin
 * Ejemplo: "16:00"
 */
const formattedEndTime = computed(() => {
  if (!props.reservationDetails?.end) return '';

  const date = new Date(props.reservationDetails.end);
  return date.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  });
});

/**
 * Calcula y formatea la duración de la reserva
 * Ejemplo: "2 horas" o "1 hora y 30 minutos"
 */
const durationText = computed(() => {
  if (!props.reservationDetails?.start || !props.reservationDetails?.end) {
    return '';
  }

  const start = new Date(props.reservationDetails.start);
  const end = new Date(props.reservationDetails.end);
  const durationMs = end - start;
  const durationMinutes = Math.floor(durationMs / 1000 / 60);
  const hours = Math.floor(durationMinutes / 60);
  const minutes = durationMinutes % 60;

  if (hours === 0) {
    return `${minutes} minuto${minutes !== 1 ? 's' : ''}`;
  } else if (minutes === 0) {
    return `${hours} hora${hours !== 1 ? 's' : ''}`;
  } else {
    return `${hours} hora${hours !== 1 ? 's' : ''} y ${minutes} minuto${minutes !== 1 ? 's' : ''}`;
  }
});

/**
 * Verifica si hay errores de validación
 */
const hasValidationErrors = computed(() => {
  return (
    (validationErrors.value && Object.keys(validationErrors.value).length > 0) ||
    error.value
  );
});

/**
 * Mensaje de error general del composable
 */
const generalError = computed(() => {
  return error.value;
});

// ============================================================================
// MÉTODOS
// ============================================================================

/**
 * Maneja la confirmación de la reserva
 * Llama al composable para crear la reserva y maneja el resultado
 */
const handleConfirm = async () => {

  // Construir el payload para la API
  const payload = {
    equipment_id: props.reservationDetails.equipmentId,
    start_time: props.reservationDetails.start,
    end_time: props.reservationDetails.end
  };

  // Llamar al composable para crear la reserva
  const reservation = await createReservation(payload);

  // Si la creación fue exitosa
  if (reservation) {

    // Emitir evento de éxito para que el padre pueda reaccionar
    emit('reservation-success', reservation);

    // Cerrar el modal
    closeModal();
  } else {
    console.error(' Error al confirmar reserva. Errores de validación:', validationErrors.value);
    // Los errores se mostrarán automáticamente en el template
  }
};

/**
 * Cierra el modal
 * Emite el evento update:show para el v-model
 */
const closeModal = () => {
  emit('update:show', false);
};

// ============================================================================
// WATCHERS
// ============================================================================

/**
 * Limpia los errores cada vez que el modal se abre
 * Esto garantiza que no se muestren errores de intentos anteriores
 */
watch(
  () => props.show,
  (newValue) => {
    if (newValue) {
      validationErrors.value = {};
    }
  }
);
</script>

<style scoped>
/**
 * Animaciones para el modal
 * Entrada y salida suaves
 */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

/**
 * Prevenir scroll en el body cuando el modal está abierto
 * Esto se maneja mejor desde el componente padre o con una librería,
 * pero se documenta aquí como recordatorio
 */
</style>
