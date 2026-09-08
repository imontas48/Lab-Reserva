<template>
  <div class="reservation-calendar-container">
    <!-- Encabezado del Calendario -->
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Disponibilidad del Equipo
      </h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
        Selecciona un rango de tiempo disponible para tu reserva
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="text-center">
        <svg
          class="mx-auto h-12 w-12 animate-spin text-blue-600"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Cargando disponibilidad...</p>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/30"
    >
      <div class="flex">
        <svg
          class="h-5 w-5 text-red-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800 dark:text-red-300">
            Error al cargar disponibilidad
          </h3>
          <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- FullCalendar Component -->
    <div v-else class="calendar-wrapper">
      <FullCalendar :options="calendarOptions" />
    </div>

    <!-- Leyenda -->
    <div class="mt-4 flex items-center justify-center space-x-6 text-sm">
      <div class="flex items-center">
        <div class="mr-2 h-4 w-4 rounded bg-red-500"></div>
        <span class="text-gray-700 dark:text-gray-300">Reservado</span>
      </div>
      <div class="flex items-center">
        <div class="mr-2 h-4 w-4 rounded bg-green-500"></div>
        <span class="text-gray-700 dark:text-gray-300">Disponible</span>
      </div>
      <div class="flex items-center">
        <svg class="mr-2 h-4 w-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
        </svg>
        <span class="text-gray-700 dark:text-gray-300">Haz clic y arrastra para seleccionar</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { useReservations } from '@/composables/useReservations';

// ============================================================================
// PROPS Y EMITS
// ============================================================================

const props = defineProps({
  /**
   * ID del equipo para el cual se mostrará la disponibilidad
   * @type {Number}
   */
  equipmentId: {
    type: Number,
    required: true
  }
});

const emit = defineEmits([
  /**
   * Evento emitido cuando el usuario selecciona un rango de tiempo
   * @param {Object} slot - { start: string (ISO 8601), end: string (ISO 8601) }
   */
  'slot-selected'
]);

// ============================================================================
// COMPOSABLES
// ============================================================================

const {
  reservations,
  loading,
  error,
  fetchReservationsForEquipment
} = useReservations();

// ============================================================================
// STATE
// ============================================================================

/**
 * Referencia al último rango de fechas visible en el calendario
 * Se usa para recargar datos cuando el usuario navega
 */
const currentDateRange = ref({
  start: null,
  end: null
});

// ============================================================================
// COMPUTED
// ============================================================================

/**
 * Transforma las reservas del composable al formato de eventos de FullCalendar
 *
 * Formato esperado por FullCalendar:
 * {
 *   title: string,
 *   start: string (ISO 8601),
 *   end: string (ISO 8601),
 *   color: string (opcional),
 *   classNames: string[] (opcional)
 * }
 */
const calendarEvents = computed(() => {
  if (!reservations.value || reservations.value.length === 0) {
    return [];
  }

  return reservations.value.map(reservation => ({
    id: reservation.id,
    title: 'Reservado',
    start: reservation.start_time,
    end: reservation.end_time,
    backgroundColor: '#ef4444', // Rojo para indicar "no disponible"
    borderColor: '#dc2626',
    textColor: '#ffffff',
    classNames: ['reservation-event'],
    // Datos adicionales para tooltips o interacciones futuras
    extendedProps: {
      reservationId: reservation.id,
      userId: reservation.user_id,
      purpose: reservation.purpose
    }
  }));
});

/**
 * Configuración completa de FullCalendar
 */
const calendarOptions = computed(() => ({
  // ===== PLUGINS =====
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],

  // ===== VISTAS =====
  initialView: 'timeGridWeek',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },

  // ===== CONFIGURACIÓN DE HORARIOS =====
  slotMinTime: '07:00:00', // Horario de apertura típico de laboratorio
  slotMaxTime: '22:00:00', // Horario de cierre
  slotDuration: '00:30:00', // Intervalos de 30 minutos
  snapDuration: '00:15:00', // Ajustar selección a intervalos de 15 min
  allDaySlot: false, // No mostrar sección "todo el día"

  // ===== INTERACTIVIDAD =====
  selectable: true, //  CRÍTICO: Permite seleccionar rangos de tiempo
  selectMirror: true, // Feedback visual mientras se selecciona
  selectOverlap: false, // NO permitir seleccionar sobre eventos existentes
  unselectAuto: true, // Deseleccionar automáticamente después de crear evento

  // ===== EVENTOS =====
  events: calendarEvents.value,

  // ===== LOCALIZACIÓN =====
  locale: 'es',
  buttonText: {
    today: 'Hoy',
    month: 'Mes',
    week: 'Semana',
    day: 'Día'
  },

  // ===== FORMATO DE HORA =====
  slotLabelFormat: {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false // Formato 24 horas
  },

  // ===== ALTURA DEL CALENDARIO =====
  height: 'auto',
  contentHeight: 600,

  // ===== EVENT HANDLERS =====

  /**
   * Manejador cuando el usuario selecciona un rango de tiempo
   * Este es el evento PRINCIPAL para la creación de reservas
   */
  select: handleSelect,

  /**
   * Manejador cuando el usuario navega a diferentes fechas
   * Se dispara al cambiar de mes, semana o día
   */
  datesSet: handleDatesSet,

  /**
   * Manejador cuando el usuario hace clic en un evento existente
   * Útil para mostrar detalles de la reserva
   */
  eventClick: handleEventClick,

  /**
   * Personalización del contenido de eventos
   */
  eventContent: renderEventContent
}));

// ============================================================================
// MÉTODOS
// ============================================================================

/**
 * Maneja la selección de un rango de tiempo por parte del usuario
 * Emite el evento 'slot-selected' con las fechas seleccionadas
 *
 * @param {Object} selectInfo - Información de la selección de FullCalendar
 */
const handleSelect = (selectInfo) => {

  // Emitir evento con las fechas seleccionadas (formato ISO 8601)
  emit('slot-selected', {
    start: selectInfo.startStr,
    end: selectInfo.endStr
  });

  // Limpiar la selección visual (el usuario ya eligió su rango)
  selectInfo.view.calendar.unselect();
};

/**
 * Maneja el cambio de vista o navegación del calendario
 * Actualiza las reservas visibles en el nuevo rango de fechas
 *
 * @param {Object} dateInfo - Información de las fechas visibles
 */
const handleDatesSet = (dateInfo) => {
  const start = dateInfo.startStr.split('T')[0]; // Extraer solo la fecha (YYYY-MM-DD)
  const end = dateInfo.endStr.split('T')[0];

  // Solo recargar si el rango de fechas cambió
  if (
    currentDateRange.value.start !== start ||
    currentDateRange.value.end !== end
  ) {

    currentDateRange.value = { start, end };

    // Recargar reservas para el nuevo rango
    fetchReservationsForEquipment(props.equipmentId, start, end);
  }
};

/**
 * Maneja el clic en un evento existente (reserva)
 * Puede usarse para mostrar detalles de la reserva en un modal
 *
 * @param {Object} clickInfo - Información del evento clickeado
 */
const handleEventClick = (clickInfo) => {
  const reservation = clickInfo.event.extendedProps;


  // TODO: Implementar modal con detalles de la reserva
  // Mostrar quién reservó, para qué, horario exacto, etc.
};

/**
 * Personaliza cómo se renderiza cada evento en el calendario
 *
 * @param {Object} eventInfo - Información del evento a renderizar
 * @returns {Object} - Objeto de configuración de renderizado
 */
const renderEventContent = (eventInfo) => {
  return {
    html: `
      <div class="fc-event-main-frame">
        <div class="fc-event-title-container">
          <div class="fc-event-title fc-sticky">
            <i class="mr-1"></i>
            ${eventInfo.event.title}
          </div>
        </div>
      </div>
    `
  };
};

/**
 * Carga inicial de reservas cuando el componente se monta
 */
const loadInitialReservations = () => {
  // Calcular rango inicial (semana actual)
  const today = new Date();
  const startOfWeek = new Date(today);
  startOfWeek.setDate(today.getDate() - today.getDay()); // Domingo

  const endOfWeek = new Date(startOfWeek);
  endOfWeek.setDate(startOfWeek.getDate() + 6); // Sábado

  const start = startOfWeek.toISOString().split('T')[0];
  const end = endOfWeek.toISOString().split('T')[0];

  currentDateRange.value = { start, end };


  fetchReservationsForEquipment(props.equipmentId, start, end);
};

// ============================================================================
// WATCHERS
// ============================================================================

/**
 * Watcher para recargar el calendario si cambia el equipmentId
 * Útil si el componente se reutiliza para diferentes equipos
 */
watch(
  () => props.equipmentId,
  (newId, oldId) => {
    if (newId && newId !== oldId) {
      loadInitialReservations();
    }
  }
);

// ============================================================================
// LIFECYCLE
// ============================================================================

onMounted(() => {
  loadInitialReservations();
});
</script>

<style scoped>
/**
 * Estilos personalizados para FullCalendar
 * Se mantienen scoped para no afectar otros componentes
 */

.reservation-calendar-container {
  @apply rounded-lg bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700;
}

.calendar-wrapper {
  @apply rounded-lg overflow-hidden;
}

/* Personalización de FullCalendar */
.calendar-wrapper :deep(.fc) {
  font-family: inherit;
}

.calendar-wrapper :deep(.fc-toolbar-title) {
  @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.calendar-wrapper :deep(.fc-button) {
  @apply bg-blue-600 border-blue-600 text-white hover:bg-blue-700;
  text-transform: capitalize;
}

.calendar-wrapper :deep(.fc-button-active) {
  @apply bg-blue-800 border-blue-800;
}

.calendar-wrapper :deep(.fc-daygrid-day-number),
.calendar-wrapper :deep(.fc-col-header-cell-cushion) {
  @apply text-gray-700 no-underline dark:text-gray-300;
}

.calendar-wrapper :deep(.fc-timegrid-slot-label) {
  @apply text-gray-600 dark:text-gray-400;
}

/* Estilo para slots seleccionables (hover) */
.calendar-wrapper :deep(.fc-timegrid-slot):hover {
  @apply bg-blue-50 dark:bg-blue-900/30;
  cursor: pointer;
}

/* Estilo para el área de selección */
.calendar-wrapper :deep(.fc-highlight) {
  @apply bg-blue-100 opacity-50 dark:bg-blue-800/50;
}

/* Eventos de reserva */
.calendar-wrapper :deep(.reservation-event) {
  cursor: not-allowed;
  opacity: 0.9;
}

.calendar-wrapper :deep(.reservation-event:hover) {
  opacity: 1;
}

/* Día actual destacado */
.calendar-wrapper :deep(.fc-day-today) {
  @apply bg-yellow-50 dark:bg-yellow-900/20;
}

/* Responsive */
@media (max-width: 768px) {
  .calendar-wrapper :deep(.fc-toolbar) {
    flex-direction: column;
    gap: 0.5rem;
  }

  .calendar-wrapper :deep(.fc-toolbar-chunk) {
    margin: 0.25rem 0;
  }
}
</style>
