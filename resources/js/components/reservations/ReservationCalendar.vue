<template>
  <div class="reservation-calendar-container">
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ heading }}</h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
        Selecciona un rango de tiempo libre para tu reserva
      </p>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <BaseSpinner size="lg" class="text-blue-600" label="Cargando disponibilidad" />
      <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Cargando disponibilidad...</p>
    </div>

    <div
      v-else-if="error"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300"
      role="alert"
    >
      {{ error }}
    </div>

    <div v-else class="calendar-wrapper">
      <FullCalendar :options="calendarOptions" />
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm">
      <div class="flex items-center">
        <div class="mr-2 h-4 w-4 rounded" :style="{ backgroundColor: EVENT_COLORS.equipment.background }"></div>
        <span class="text-gray-700 dark:text-gray-300">Equipo reservado</span>
      </div>
      <div class="flex items-center">
        <div class="mr-2 h-4 w-4 rounded" :style="{ backgroundColor: EVENT_COLORS.lab.background }"></div>
        <span class="text-gray-700 dark:text-gray-300">Clase (laboratorio completo)</span>
      </div>
      <div class="flex items-center">
        <div class="mr-2 h-4 w-4 rounded" :style="{ backgroundColor: EVENT_COLORS.pendingLab.background }"></div>
        <span class="text-gray-700 dark:text-gray-300">Clase pendiente de aprobación</span>
      </div>
      <div v-if="schedule" class="flex items-center">
        <div class="mr-2 h-4 w-4 rounded bg-gray-400"></div>
        <span class="text-gray-700 dark:text-gray-300">Cerrado</span>
      </div>
      <span class="text-gray-500 dark:text-gray-400">Haz clic y arrastra para seleccionar</span>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import BaseSpinner from '@/components/ui/BaseSpinner.vue';
import { useReservations } from '@/composables/useReservations';
import {
  EVENT_COLORS, closuresToEvents, mapReservationsToEvents, openingHoursToBusinessHours,
} from '@/utils/calendarEvents';
import { toApiDateTime, toLocalDateString } from '@/utils/datetime';

/**
 * Calendario de disponibilidad de un equipo o de un laboratorio completo.
 *
 * En ambos casos pinta las reservas de los dos tipos: una clase bloquea
 * todos los equipos, y un equipo reservado impide apartar el laboratorio.
 */
const props = defineProps({
  equipmentId: { type: Number, default: null },
  labId: { type: Number, default: null },
  /** { opening_hours, closures } del laboratorio, si se conoce */
  schedule: { type: Object, default: null },
});

const emit = defineEmits(['slot-selected', 'event-click']);

const { reservations, loading, error, fetchReservationsForEquipment, fetchReservationsForLab } = useReservations();

const context = computed(() => (props.equipmentId ? 'equipment' : 'lab'));
const heading = computed(() => (context.value === 'equipment'
  ? 'Disponibilidad del equipo'
  : 'Disponibilidad del laboratorio'));

const currentDateRange = ref({ start: null, end: null });

const calendarEvents = computed(() => [
  ...mapReservationsToEvents(reservations.value, { context: context.value }),
  ...closuresToEvents(props.schedule?.closures),
]);

const businessHours = computed(() => openingHoursToBusinessHours(props.schedule?.opening_hours));

const calendarOptions = computed(() => ({
  // Sombrea las horas de cierre y no deja seleccionar fuera del horario ni
  // sobre un cierre: la misma regla que aplica el servidor.
  ...(businessHours.value ? { businessHours: businessHours.value, selectConstraint: 'businessHours' } : {}),
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'timeGridWeek',
  headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
  slotMinTime: '07:00:00',
  slotMaxTime: '22:00:00',
  slotDuration: '00:30:00',
  snapDuration: '00:15:00',
  allDaySlot: false,
  selectable: true,
  selectMirror: true,
  // No se puede seleccionar sobre ningún bloque: ni sobre una clase ni sobre
  // un equipo ya reservado, que es exactamente la regla del servidor.
  selectOverlap: false,
  unselectAuto: true,
  events: calendarEvents.value,
  locale: 'es',
  buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día' },
  slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  height: 'auto',
  contentHeight: 600,
  select: handleSelect,
  datesSet: handleDatesSet,
  eventClick: (info) => emit('event-click', info.event.extendedProps),
}));

function handleSelect(selectInfo) {
  emit('slot-selected', {
    // startStr es hora local SIN offset; se normaliza al mismo formato que
    // emite el formulario rápido.
    start: toApiDateTime(selectInfo.start),
    end: toApiDateTime(selectInfo.end),
  });

  selectInfo.view.calendar.unselect();
}

function load(start, end) {
  currentDateRange.value = { start, end };

  return context.value === 'equipment'
    ? fetchReservationsForEquipment(props.equipmentId, start, end)
    : fetchReservationsForLab(props.labId, start, end);
}

function handleDatesSet(dateInfo) {
  const start = dateInfo.startStr.split('T')[0];
  const end = dateInfo.endStr.split('T')[0];

  if (currentDateRange.value.start !== start || currentDateRange.value.end !== end) {
    load(start, end);
  }
}

function loadInitialReservations() {
  const today = new Date();
  const startOfWeek = new Date(today);
  startOfWeek.setDate(today.getDate() - today.getDay());
  const endOfWeek = new Date(startOfWeek);
  endOfWeek.setDate(startOfWeek.getDate() + 6);

  load(toLocalDateString(startOfWeek), toLocalDateString(endOfWeek));
}

watch(() => [props.equipmentId, props.labId], ([equipmentId, labId], [oldEquipmentId, oldLabId]) => {
  if (equipmentId !== oldEquipmentId || labId !== oldLabId) {
    loadInitialReservations();
  }
});

onMounted(loadInitialReservations);
</script>

<style scoped>
.reservation-calendar-container {
  @apply rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800;
}

.calendar-wrapper {
  @apply overflow-hidden rounded-lg;
}

.calendar-wrapper :deep(.fc) {
  font-family: inherit;
}

.calendar-wrapper :deep(.fc-toolbar-title) {
  @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.calendar-wrapper :deep(.fc-button) {
  @apply border-blue-600 bg-blue-600 text-white hover:bg-blue-700;
  text-transform: capitalize;
}

.calendar-wrapper :deep(.fc-col-header-cell),
.calendar-wrapper :deep(.fc-timegrid-slot-label) {
  @apply text-gray-700 dark:text-gray-300;
}

.calendar-wrapper :deep(.fc-theme-standard td),
.calendar-wrapper :deep(.fc-theme-standard th) {
  @apply border-gray-200 dark:border-gray-700;
}
</style>
