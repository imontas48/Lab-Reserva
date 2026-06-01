<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nueva Reserva</h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
        Selecciona un laboratorio, luego un equipo y elige tu horario
      </p>
    </div>

    <!-- ========================================================================= -->
    <!-- PASO 1: SELECCIÓN DE LABORATORIO -->
    <!-- ========/**
 * Maneja el cambio en el select de equipos (solo actualiza el preview)
 */
const handleEquipmentSelection = () => {
  console.log('📝 Equipo seleccionado:', selectedEquipmentPreview.value?.name || selectedEquipmentPreview.value?.identifier);
};=========================================================== -->
    <div v-if="!selectedLab" class="rounded-lg bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 font-bold text-sm mr-3">1</span>
        Selecciona un Laboratorio
      </h2>

      <!-- Loading -->
      <div v-if="loadingLabs" class="flex items-center justify-center py-12">
        <div class="text-center">
          <svg
            class="mx-auto h-12 w-12 animate-spin text-blue-600"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Cargando laboratorios...</p>
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="errorLabs" class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/30">
        <div class="flex">
          <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Error al cargar laboratorios</h3>
            <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ errorLabs }}</p>
          </div>
        </div>
      </div>

      <!-- Grid de laboratorios -->
      <div v-else-if="activeLabs.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <button
          v-for="lab in activeLabs"
          :key="lab.id"
          @click="selectLab(lab)"
          class="group relative flex flex-col rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-all hover:border-blue-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-blue-400"
        >
          <!-- Icono del laboratorio -->
          <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 group-hover:bg-blue-200">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
          </div>

          <!-- Nombre del laboratorio -->
          <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
            {{ lab.name }}
          </h3>

          <!-- Ubicación -->
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ lab.location }}
          </p>

          <!-- Capacidad -->
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Capacidad: {{ lab.capacity }} {{ lab.capacity === 1 ? 'persona' : 'personas' }}
          </p>

          <!-- Flecha indicadora (aparece al hover) -->
          <div class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 transition-opacity group-hover:opacity-100">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </button>
      </div>

      <!-- Estado vacío -->
      <div v-else class="py-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
          No hay laboratorios disponibles
        </h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          No se encontraron laboratorios activos en el sistema
        </p>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PASO 2: SELECCIÓN DE EQUIPO -->
    <!-- ========================================================================= -->
    <div v-else-if="selectedLab && !selectedEquipment" class="rounded-lg bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
      <!-- Breadcrumb / Laboratorio seleccionado -->
      <div class="mb-6 rounded-lg bg-blue-50 p-4 border border-blue-200 dark:bg-blue-900/30 dark:border-blue-800">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <div class="ml-4">
              <p class="text-sm font-medium text-blue-900 dark:text-blue-300">Laboratorio seleccionado:</p>
              <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">{{ selectedLab.name }}</h3>
              <p class="text-sm text-blue-700 dark:text-blue-400">{{ selectedLab.location }}</p>
            </div>
          </div>
          <button
            @click="clearLabSelection"
            class="inline-flex items-center rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-gray-700 dark:border-blue-600 dark:text-blue-300 dark:hover:bg-gray-600"
          >
            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Cambiar laboratorio
          </button>
        </div>
      </div>

      <!-- Título del paso 2 -->
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-600 font-bold text-sm mr-3">2</span>
        Selecciona un Equipo
      </h2>

      <!-- Loading equipos -->
      <div v-if="loadingEquipment" class="flex items-center justify-center py-12">
        <div class="text-center">
          <svg
            class="mx-auto h-12 w-12 animate-spin text-blue-600"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Cargando equipos del laboratorio...</p>
        </div>
      </div>

      <!-- Error equipos -->
      <div v-else-if="errorEquipment" class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/30">
        <div class="flex">
          <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Error al cargar equipos</h3>
            <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ errorEquipment }}</p>
          </div>
        </div>
      </div>

      <!-- Select de equipos -->
      <div v-else-if="labEquipment.length > 0">
        <label for="equipment-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Selecciona un equipo del laboratorio
        </label>

        <select
          id="equipment-select"
          v-model="selectedEquipmentId"
          @change="handleEquipmentSelection"
          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base py-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        >
          <option :value="null" disabled>-- Selecciona un equipo --</option>
          <option
            v-for="eq in labEquipment"
            :key="eq.id"
            :value="eq.id"
            :disabled="eq.status?.status === 'out_of_service'"
          >
            {{ eq.name || eq.identifier }} — {{ eq.status?.details || 'Cargando...' }}
          </option>
        </select>

        <!-- Información del equipo seleccionado (preview) -->
        <div v-if="selectedEquipmentId" class="mt-4 rounded-lg bg-gray-50 p-4 border border-gray-200 dark:bg-gray-700 dark:border-gray-600">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Equipo seleccionado:</h4>
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              <div class="ml-3">
                <p class="text-base font-semibold text-gray-900 dark:text-white">
                  {{ selectedEquipmentPreview?.name || selectedEquipmentPreview?.identifier }}
                </p>
              </div>
            </div>

            <!-- Badge de estado dinámico -->
            <span
              v-if="selectedEquipmentPreview?.status"
              :class="{
                'bg-green-100 text-green-800': selectedEquipmentPreview.status.color === 'green',
                'bg-blue-100 text-blue-800': selectedEquipmentPreview.status.color === 'blue',
                'bg-yellow-100 text-yellow-800': selectedEquipmentPreview.status.color === 'yellow',
                'bg-red-100 text-red-800': selectedEquipmentPreview.status.color === 'red'
              }"
              class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
            >
              <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="selectedEquipmentPreview.status.icon === 'check'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                <path v-else-if="selectedEquipmentPreview.status.icon === 'clock'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                <path v-else-if="selectedEquipmentPreview.status.icon === 'calendar'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
              {{ selectedEquipmentPreview.status.details }}
            </span>
          </div>
          <!-- Aviso informativo cuando hay reservas en otros horarios (no bloquea) -->
          <div
            v-if="selectedEquipmentPreview?.status?.status === 'reserved' || selectedEquipmentPreview?.status?.status === 'in_use'"
            class="mt-3 rounded-lg bg-blue-50 border border-blue-200 p-3 dark:bg-blue-900/30 dark:border-blue-800"
          >
            <p class="text-sm text-blue-800 dark:text-blue-300">
              <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Este equipo tiene reservas en algunos horarios. En el calendario podrás elegir un horario libre disponible.
            </p>
          </div>

          <!-- Aviso de fuera de servicio (bloquea la selección) -->
          <div
            v-else-if="selectedEquipmentPreview?.status?.status === 'out_of_service'"
            class="mt-3 rounded-lg bg-red-50 border border-red-200 p-3 dark:bg-red-900/30 dark:border-red-800"
          >
            <p class="text-sm text-red-800 dark:text-red-300">
              <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
              Este equipo está en mantenimiento y no puede ser reservado. Por favor selecciona otro.
            </p>
          </div>

          <button
            v-if="selectedEquipmentPreview?.status?.status !== 'out_of_service'"
            @click="confirmEquipmentSelection"
            class="mt-4 w-full inline-flex justify-center items-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            Continuar con este equipo
            <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Estado vacío -->
      <div v-else class="py-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
          No hay equipos en este laboratorio
        </h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          Este laboratorio no tiene equipos asociados en el sistema
        </p>
      </div>
    </div>


    <!-- ========================================================================= -->
    <!-- PASO 3: CALENDARIO Y SELECCIÓN DE HORARIO -->
    <!-- ========================================================================= -->
    <div v-else class="space-y-6">
      <!-- Breadcrumb completo -->
      <div class="rounded-lg bg-gradient-to-r from-blue-50 to-green-50 p-4 border border-blue-200 dark:from-blue-900/30 dark:to-green-900/30 dark:border-blue-800">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <!-- Ruta de selección -->
          <div class="flex items-center flex-wrap gap-4">
            <!-- Laboratorio -->
            <div class="flex items-center">
              <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              <div class="ml-3">
                <p class="text-xs font-medium text-blue-900 dark:text-blue-300">Laboratorio:</p>
                <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">{{ selectedLab.name }}</p>
              </div>
            </div>

            <!-- Separador -->
            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>

            <!-- Equipo -->
            <div class="flex items-center">
              <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              <div class="ml-3">
                <p class="text-xs font-medium text-green-900 dark:text-green-300">Equipo:</p>
                <p class="text-sm font-semibold text-green-900 dark:text-green-100">
                  {{ selectedEquipment.name || selectedEquipment.identifier }}
                </p>
                <!-- Badge de estado en breadcrumb -->
                <span
                  v-if="selectedEquipment.status"
                  :class="{
                    'bg-green-100 text-green-800': selectedEquipment.status.color === 'green',
                    'bg-blue-100 text-blue-800': selectedEquipment.status.color === 'blue',
                    'bg-yellow-100 text-yellow-800': selectedEquipment.status.color === 'yellow',
                    'bg-red-100 text-red-800': selectedEquipment.status.color === 'red'
                  }"
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium mt-1"
                >
                  {{ selectedEquipment.status.details }}
                </span>
              </div>
            </div>
          </div>

          <!-- Botón de cambio -->
          <button
            @click="clearEquipmentSelection"
            class="inline-flex items-center rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-gray-700 dark:border-blue-600 dark:text-blue-300 dark:hover:bg-gray-600"
          >
            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Cambiar equipo
          </button>
        </div>
      </div>

      <!-- Título del paso 3 -->
      <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
          <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 text-purple-600 font-bold text-sm mr-3">3</span>
          Selecciona tu Horario
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 ml-11">
          Usa el formulario para elegir fecha y hora, o haz clic y arrastra directamente en el calendario
        </p>
      </div>

      <!-- ===== Formulario de selección rápida ===== -->
      <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          Selección rápida de fecha y hora
        </h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <!-- Fecha -->
          <div>
            <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">
              Fecha
            </label>
            <VueDatePicker
              v-model="quickDate"
              :dark="isDark"
              :min-date="today"
              locale="es"
              :format="'dd/MM/yyyy'"
              :enable-time-picker="false"
              :clearable="true"
              auto-apply
              placeholder="dd/mm/aaaa"
            />
          </div>

          <!-- Hora de inicio -->
          <div>
            <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">
              Hora de inicio
            </label>
            <VueDatePicker
              v-model="quickStartTime"
              :dark="isDark"
              time-picker
              :clearable="true"
              :minutes-increment="15"
              :min-time="{ hours: 7, minutes: 0 }"
              :max-time="{ hours: 21, minutes: 30 }"
              placeholder="HH:mm"
            />
          </div>

          <!-- Hora de fin -->
          <div>
            <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">
              Hora de fin
            </label>
            <VueDatePicker
              v-model="quickEndTime"
              :dark="isDark"
              time-picker
              :clearable="true"
              :minutes-increment="15"
              :min-time="{ hours: 7, minutes: 30 }"
              :max-time="{ hours: 22, minutes: 0 }"
              placeholder="HH:mm"
            />
          </div>
        </div>

        <!-- Error de validación -->
        <p v-if="quickSelectError" class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
          <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ quickSelectError }}
        </p>

        <!-- Vista previa del horario -->
        <div
          v-if="quickDate && quickStartTime && quickEndTime && !quickSelectError"
          class="mt-3 rounded-lg bg-purple-50 border border-purple-200 px-4 py-2.5 text-sm text-purple-800 dark:bg-purple-900/30 dark:border-purple-800 dark:text-purple-300 flex items-center gap-2"
        >
          <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ formatQuickPreview }}
        </div>

        <button
          :disabled="!quickDate || !quickStartTime || !quickEndTime || !!quickSelectError"
          @click="handleQuickSelect"
          class="mt-4 inline-flex items-center gap-2 rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Confirmar este horario
        </button>
      </div>

      <!-- Calendario de disponibilidad -->
      <ReservationCalendar
        :equipment-id="selectedEquipment.id"
        @slot-selected="handleSlotSelected"
      />
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL DE CONFIRMACIÓN -->
    <!-- ========================================================================= -->
    <CreateReservationModal
      v-model:show="showModal"
      :reservation-details="reservationDetails"
      :equipment-name="selectedEquipment?.name || selectedEquipment?.identifier || ''"
      @reservation-success="handleReservationSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useEquipment } from '@/composables/useEquipment';
import { useLabs } from '@/composables/useLabs';
import { useToast } from '@/composables/useToast';
import ReservationCalendar from '@/components/reservations/ReservationCalendar.vue';
import CreateReservationModal from '@/components/reservations/CreateReservationModal.vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

// ============================================================================
// COMPOSABLES
// ============================================================================

const router = useRouter();
const {
  equipment,
  loading: loadingEquipment,
  error: errorEquipment,
  fetchEquipment
} = useEquipment();

const {
  labs,
  loading: loadingLabs,
  error: errorLabs,
  fetchLabs
} = useLabs();

const toast = useToast();

// ============================================================================
// STATE
// ============================================================================

/**
 * Laboratorio seleccionado por el usuario (Paso 1)
 */
const selectedLab = ref(null);

/**
 * Equipo seleccionado por el usuario (Paso 2)
 */
const selectedEquipment = ref(null);

/**
 * ID del equipo seleccionado en el select (temporal)
 */
const selectedEquipmentId = ref(null);

/**
 * Controla la visibilidad del modal de confirmación
 */
const showModal = ref(false);

/**
 * Detalles de la reserva a confirmar
 * Estructura: { equipmentId: Number, start: String, end: String }
 */
const reservationDetails = ref(null);

// --- Selección rápida de fecha/hora (Paso 3) ---
/** Fecha elegida en el formulario rápido (objeto Date o null) */
const quickDate = ref(null);
/** Hora de inicio elegida ({ hours, minutes } o null) */
const quickStartTime = ref(null);
/** Hora de fin elegida ({ hours, minutes } o null) */
const quickEndTime = ref(null);
/** Fecha mínima permitida en el datepicker (hoy) */
const today = new Date();
/** Detecta el tema oscuro activo para pasárselo al datepicker */
const isDark = ref(document.documentElement.classList.contains('dark'));
const themeObserver = new MutationObserver(() => {
  isDark.value = document.documentElement.classList.contains('dark');
});
onMounted(() => {
  themeObserver.observe(document.documentElement, { attributeFilter: ['class'] });
});

// ============================================================================
// COMPUTED
// ============================================================================

/**
 * Filtra solo laboratorios activos
 */
const activeLabs = computed(() => {
  if (!labs.value) return [];
  return labs.value.filter(lab => lab.is_active);
});

/**
 * Equipos del laboratorio seleccionado
 */
const labEquipment = computed(() => {
  if (!selectedLab.value || !equipment.value) return [];

  return equipment.value.filter(eq => eq.lab_id === selectedLab.value.id);
});

/**
 * Preview del equipo seleccionado en el select
 */
const selectedEquipmentPreview = computed(() => {
  if (!selectedEquipmentId.value) return null;
  return labEquipment.value.find(eq => eq.id === selectedEquipmentId.value);
});

/**
 * Convierte el valor del datepicker de tiempo ({ hours, minutes }) a 'HH:mm'.
 */
const timeToString = (t) => {
  if (!t) return '';
  const h = String(t.hours).padStart(2, '0');
  const m = String(t.minutes).padStart(2, '0');
  return `${h}:${m}`;
};

/**
 * Valida la selección rápida de fecha/hora en tiempo real.
 * Retorna un mensaje de error o null si todo está bien.
 */
const quickSelectError = computed(() => {
  if (!quickDate.value || !quickStartTime.value || !quickEndTime.value) return null;

  const dateStr = quickDate.value instanceof Date
    ? quickDate.value.toISOString().split('T')[0]
    : quickDate.value;
  const startStr = timeToString(quickStartTime.value);
  const endStr   = timeToString(quickEndTime.value);

  const start = new Date(`${dateStr}T${startStr}`);
  const end   = new Date(`${dateStr}T${endStr}`);

  if (start < new Date()) return 'La fecha y hora de inicio no puede estar en el pasado.';
  if (end <= start) return 'La hora de fin debe ser posterior a la hora de inicio.';

  const diffMinutes = (end - start) / 60000;
  if (diffMinutes < 30) return 'La reserva debe tener al menos 30 minutos de duración.';
  if (diffMinutes > 480) return 'La reserva no puede superar las 8 horas.';

  return null;
});

/**
 * Muestra una vista previa legible del horario seleccionado con el formulario rápido.
 */
const formatQuickPreview = computed(() => {
  if (!quickDate.value || !quickStartTime.value || !quickEndTime.value) return '';

  const dateStr = quickDate.value instanceof Date
    ? quickDate.value.toISOString().split('T')[0]
    : quickDate.value;
  const startStr = timeToString(quickStartTime.value);
  const endStr   = timeToString(quickEndTime.value);

  const dateLabel = new Intl.DateTimeFormat('es-ES', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
  }).format(new Date(`${dateStr}T${startStr}`));

  return `${dateLabel} · ${startStr} – ${endStr} hrs`;
});

// ============================================================================
// MÉTODOS
// ============================================================================

/**
 * Selecciona un laboratorio y avanza al paso 2
 */
const selectLab = async (lab) => {
  console.log('📌 Laboratorio seleccionado:', lab.name);
  selectedLab.value = lab;

  // Cargar equipos del sistema
  await fetchEquipment();
};

/**
 * Limpia la selección de laboratorio y vuelve al paso 1
 */
const clearLabSelection = () => {
  console.log('🔄 Limpiando selección de laboratorio...');
  selectedLab.value = null;
  selectedEquipment.value = null;
  selectedEquipmentId.value = null;
  reservationDetails.value = null;
  showModal.value = false;
};

/**
 * Maneja el cambio en el select de equipos (solo actualiza el preview)
 */
const handleEquipmentSelection = () => {
  console.log('� Equipo seleccionado en preview:', selectedEquipmentId.value);
};

/**
 * Confirma la selección de equipo y avanza al paso 3 (calendario)
 */
const confirmEquipmentSelection = () => {
  if (!selectedEquipmentId.value) return;

  const equipment = labEquipment.value.find(eq => eq.id === selectedEquipmentId.value);

  if (!equipment) {
    toast.error('No se pudo encontrar el equipo seleccionado');
    return;
  }

  // Validación usando el nuevo sistema de estados dinámicos
  if (equipment.status?.status === 'out_of_service') {
    toast.error('Este equipo está en mantenimiento y no puede ser reservado.');
    return;
  }

  console.log('✅ Confirmando selección de equipo:', equipment);
  selectedEquipment.value = equipment;
};

/**
 * Limpia la selección de equipo y vuelve al paso 2
 */
const clearEquipmentSelection = () => {
  console.log('🔄 Limpiando selección de equipo...');
  selectedEquipment.value = null;
  selectedEquipmentId.value = null;
  reservationDetails.value = null;
  showModal.value = false;
  quickDate.value = null;
  quickStartTime.value = null;
  quickEndTime.value = null;
};

/**
 * Maneja la selección de un slot de tiempo en el calendario
 * Abre el modal de confirmación
 */
const handleSlotSelected = (slot) => {
  console.log('📅 Slot seleccionado:', slot);

  reservationDetails.value = {
    equipmentId: selectedEquipment.value.id,
    start: slot.start,
    end: slot.end
  };

  showModal.value = true;
};

/**
 * Construye las fechas ISO desde el formulario de selección rápida
 * y abre el mismo modal de confirmación que el calendario.
 */
const handleQuickSelect = () => {
  if (quickSelectError.value) return;

  const dateStr = quickDate.value instanceof Date
    ? quickDate.value.toISOString().split('T')[0]
    : quickDate.value;
  const startStr = timeToString(quickStartTime.value);
  const endStr   = timeToString(quickEndTime.value);

  const start = new Date(`${dateStr}T${startStr}`).toISOString();
  const end   = new Date(`${dateStr}T${endStr}`).toISOString();

  handleSlotSelected({ start, end });
};

/**
 * Maneja el éxito de la creación de reserva
 * Muestra notificación y redirige
 */
const handleReservationSuccess = (reservation) => {
  console.log('✅ Reserva creada exitosamente:', reservation);

  // Mostrar notificación de éxito
  toast.success('¡Reserva confirmada exitosamente!');

  // Cerrar modal
  showModal.value = false;

  // Redirigir a "Mis Reservas"
  setTimeout(() => {
    router.push('/reservations');
  }, 1500);
};

// ============================================================================
// WATCHERS
// ============================================================================

/**
 * Al cambiar de laboratorio, resetear equipo seleccionado
 */
watch(selectedLab, (newLab) => {
  if (newLab) {
    selectedEquipmentId.value = null;
    selectedEquipment.value = null;
  }
});

// ============================================================================
// LIFECYCLE
// ============================================================================

onMounted(async () => {
  console.log('🚀 Cargando laboratorios...');

  // Solo cargar laboratorios al inicio
  // Los equipos se cargarán cuando se seleccione un laboratorio
  await fetchLabs();
});
</script>

<style scoped>
/* ─── VueDatePicker — integración con el tema de la app ─── */

/* Modo claro: variables de color */
:deep(.dp__theme_light) {
  --dp-background-color: #ffffff;
  --dp-text-color: #111827;
  --dp-hover-color: #f3f4f6;
  --dp-hover-text-color: #111827;
  --dp-hover-icon-color: #6b7280;
  --dp-primary-color: #7c3aed;          /* purple-700 — acento de la sección */
  --dp-primary-text-color: #ffffff;
  --dp-secondary-color: #e5e7eb;
  --dp-border-color: #d1d5db;           /* gray-300 */
  --dp-menu-border-color: #d1d5db;
  --dp-border-color-hover: #9ca3af;     /* gray-400 */
  --dp-border-color-focus: #7c3aed;     /* purple-700 */
  --dp-disabled-color: #f3f4f6;
  --dp-scroll-bar-background: #f3f4f6;
  --dp-scroll-bar-color: #d1d5db;
  --dp-success-color: #16a34a;
  --dp-success-color-disabled: #bbf7d0;
  --dp-icon-color: #6b7280;
  --dp-danger-color: #dc2626;
  --dp-highlight-color: #ede9fe;        /* purple-100 */
  --dp-font-size: 0.875rem;
  --dp-border-radius: 0.5rem;
}

/* Modo oscuro: variables de color */
:deep(.dp__theme_dark) {
  --dp-background-color: #111827;       /* gray-900 */
  --dp-text-color: #f9fafb;
  --dp-hover-color: #1f2937;            /* gray-800 */
  --dp-hover-text-color: #f9fafb;
  --dp-hover-icon-color: #9ca3af;
  --dp-primary-color: #8b5cf6;          /* purple-500 */
  --dp-primary-text-color: #ffffff;
  --dp-secondary-color: #374151;
  --dp-border-color: #4b5563;           /* gray-600 */
  --dp-menu-border-color: #374151;
  --dp-border-color-hover: #6b7280;     /* gray-500 */
  --dp-border-color-focus: #8b5cf6;
  --dp-disabled-color: #1f2937;
  --dp-scroll-bar-background: #1f2937;
  --dp-scroll-bar-color: #374151;
  --dp-success-color: #22c55e;
  --dp-success-color-disabled: #166534;
  --dp-icon-color: #9ca3af;
  --dp-danger-color: #ef4444;
  --dp-highlight-color: #4c1d95;        /* purple-900 */
  --dp-font-size: 0.875rem;
  --dp-border-radius: 0.5rem;
}

/* Input trigger: mismo alto y padding que BaseInput */
:deep(.dp__input) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

/* Quitar el anillo de foco nativo; VueDatePicker aplica el suyo con variables */
:deep(.dp__input:focus) {
  outline: none;
}
</style>
