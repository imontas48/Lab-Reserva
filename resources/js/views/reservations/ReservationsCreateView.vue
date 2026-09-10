<template>
  <div class="space-y-6">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nueva reserva</h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ subtitle }}</p>
    </div>

    <div
      v-if="blockedUntil"
      class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900 dark:border-orange-800 dark:bg-orange-900/30 dark:text-orange-200"
      role="alert"
    >
      Tus reservas están bloqueadas hasta el <strong>{{ blockedUntil }}</strong> por inasistencias reiteradas.
      Recuerda registrar tu llegada con el código de cada reserva.
    </div>

    <!-- Paso 1: laboratorio -->
    <LabPicker
      v-if="!selectedLab"
      :labs="labs"
      :loading="loadingLabs"
      :error="errorLabs"
      @select="selectLab"
    />

    <!-- Paso 2 (solo con permiso): equipo o laboratorio completo -->
    <ReservationModePicker
      v-else-if="!mode"
      :lab="selectedLab"
      :auto-approved="authStore.canApproveReservations"
      @select="selectMode"
      @back="reset"
    />

    <!-- Paso 2/3: equipo -->
    <EquipmentPicker
      v-else-if="mode === 'equipment' && !selectedEquipment"
      :lab="selectedLab"
      :equipment="equipment"
      :loading="loadingEquipment"
      :error="errorEquipment"
      :step-number="authStore.canCreateLabReservation ? 3 : 2"
      @select="(eq) => selectedEquipment = eq"
      @back="authStore.canCreateLabReservation ? (mode = null) : reset()"
    />

    <!-- Último paso: franja -->
    <div v-else class="space-y-6">
      <ReservationSelectionBar
        :lab="selectedLab"
        :mode="mode"
        :equipment="selectedEquipment"
        @change-lab="reset"
        @change-equipment="selectedEquipment = null"
      />

      <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="flex items-center text-xl font-semibold text-gray-900 dark:text-white">
          <span class="mr-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-600">{{ lastStepNumber }}</span>
          Selecciona tu horario
        </h2>
        <p class="ml-11 mt-1 text-sm text-gray-600 dark:text-gray-300">
          Usa el formulario para elegir fecha y hora, o haz clic y arrastra directamente en el calendario.
        </p>
      </div>

      <QuickSlotForm
        :max-hours="limits.max_hours ?? 8"
        :min-minutes="limits.min_minutes ?? 30"
        @slot-selected="openConfirmation"
      />

      <ReservationCalendar
        :equipment-id="mode === 'equipment' ? selectedEquipment.id : null"
        :lab-id="mode === 'lab' ? selectedLab.id : null"
        :schedule="schedule"
        @slot-selected="openConfirmation"
      />
    </div>

    <CreateReservationModal
      v-if="reservationDetails"
      v-model:show="showModal"
      :mode="mode ?? 'equipment'"
      :reservation-details="reservationDetails"
      :target-name="targetName"
      :auto-approved="authStore.canApproveReservations"
      @reservation-success="handleReservationSuccess"
      @series-success="handleSeriesSuccess"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useEquipment } from '@/composables/useEquipment';
import { useLabs } from '@/composables/useLabs';
import { useLabSchedule } from '@/composables/useLabSchedule';
import { useToast } from '@/composables/useToast';
import LabPicker from '@/components/reservations/LabPicker.vue';
import ReservationModePicker from '@/components/reservations/ReservationModePicker.vue';
import EquipmentPicker from '@/components/reservations/EquipmentPicker.vue';
import ReservationSelectionBar from '@/components/reservations/ReservationSelectionBar.vue';
import QuickSlotForm from '@/components/reservations/QuickSlotForm.vue';
import ReservationCalendar from '@/components/reservations/ReservationCalendar.vue';
import CreateReservationModal from '@/components/reservations/CreateReservationModal.vue';
import { isPending } from '@/utils/reservationStatus';

/**
 * Asistente de reserva. Orquesta los pasos; cada paso es un componente.
 *
 * Flujo: laboratorio → (modalidad, solo con reservations.createLab) →
 * equipo (si modalidad equipo) → franja → confirmación.
 */
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const { labs, loading: loadingLabs, error: errorLabs, fetchLabs } = useLabs();
const { equipment, loading: loadingEquipment, error: errorEquipment, fetchEquipment } = useEquipment();
const { schedule, fetchSchedule } = useLabSchedule();

const selectedLab = ref(null);
const mode = ref(null);
const selectedEquipment = ref(null);
const showModal = ref(false);
const reservationDetails = ref(null);

const limits = computed(() => authStore.reservationLimits ?? {});

const blockedUntil = computed(() => {
  const until = authStore.user?.reservation_blocked_until;

  if (!until || new Date(until) <= new Date()) return null;

  return new Intl.DateTimeFormat('es-ES', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(until));
});

const subtitle = computed(() => (authStore.canCreateLabReservation
  ? 'Elige un laboratorio, decide si reservas un equipo o el laboratorio completo, y selecciona tu horario.'
  : 'Selecciona un laboratorio, luego un equipo y elige tu horario.'));

const lastStepNumber = computed(() => {
  if (!authStore.canCreateLabReservation) return 3;

  return mode.value === 'lab' ? 3 : 4;
});

const targetName = computed(() => (mode.value === 'lab'
  ? selectedLab.value?.name ?? ''
  : selectedEquipment.value?.identifier ?? ''));

async function selectLab(lab) {
  selectedLab.value = lab;

  // El horario sombrea el calendario y bloquea la selección fuera de hora;
  // si no se puede cargar, el servidor sigue validando.
  fetchSchedule(lab.id).catch(() => {});

  if (!authStore.canCreateLabReservation) {
    await selectMode('equipment');
  }
}

async function selectMode(selected) {
  mode.value = selected;

  if (selected === 'equipment') {
    // Filtrado en el servidor: antes se traía la primera página de TODOS los
    // equipos y se filtraba en cliente, así que un laboratorio grande perdía
    // puestos.
    try {
      await fetchEquipment({ lab_id: selectedLab.value.id, per_page: 100 });
    } catch {
      /* errorEquipment ya lo muestra el picker */
    }
  }
}

function reset() {
  schedule.value = null;
  selectedLab.value = null;
  mode.value = null;
  selectedEquipment.value = null;
  reservationDetails.value = null;
  showModal.value = false;
}

function openConfirmation(slot) {
  reservationDetails.value = {
    start: slot.start,
    end: slot.end,
    equipmentId: mode.value === 'equipment' ? selectedEquipment.value.id : null,
    labId: mode.value === 'lab' ? selectedLab.value.id : null,
  };

  showModal.value = true;
}

let redirectTimer = null;

function handleReservationSuccess(reservation) {
  toast.success(isPending(reservation)
    ? 'Solicitud enviada. Te avisaremos cuando el administrador la revise.'
    : '¡Reserva confirmada exitosamente!');

  showModal.value = false;

  // Se guarda la referencia: sin cancelarlo, si el usuario navegaba a otra
  // pantalla en ese segundo y medio, el temporizador lo sacaba de donde
  // estuviera.
  redirectTimer = setTimeout(() => router.push('/reservations'), 1500);
}

function handleSeriesSuccess(series) {
  const created = series.created?.length ?? 0;
  const skipped = series.skipped?.length ?? 0;
  const pending = series.created?.[0] && isPending(series.created[0]);

  toast.success(pending
    ? `Solicitud de ${created} clases enviada. Te avisaremos cuando el administrador la revise.`
    : `Serie creada: ${created} clases.`);

  if (skipped > 0) {
    toast.warning(`${skipped} ${skipped === 1 ? 'fecha se omitió' : 'fechas se omitieron'} por conflicto o cierre: ${series.skipped.map((s) => s.date).join(', ')}.`);
  }

  showModal.value = false;
  redirectTimer = setTimeout(() => router.push('/reservations'), 2500);
}

onUnmounted(() => clearTimeout(redirectTimer));

onMounted(async () => {
  try {
    await fetchLabs({ per_page: 100 });
  } catch {
    /* errorLabs ya lo muestra el picker */
  }
});
</script>
