<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         VISTA: Inventario de Equipos
         ═══════════════════════════════════════════════════════════════════════════

         PROPÓSITO:
         Vista principal para listar todos los equipos registrados en el sistema.
         Permite visualizar, editar y eliminar registros de equipos.

         ARQUITECTURA:
         Esta vista implementa el patrón "Composable + Vista + Componente Reutilizable":
         - useEquipment: Maneja la lógica de datos
         - EquipmentIndexView: Orquesta la presentación
         - DataTable: Renderiza los datos con soporte para datos anidados

         CARACTERÍSTICAS AVANZADAS:
         - Soporte para datos relacionales (lab.name usando dot notation)
         - Renderizado condicional de estado operacional con badges
         - Clases CSS centralizadas siguiendo patrón de refactorización

         ═══════════════════════════════════════════════════════════════════════════ -->

    <div class="container mx-auto px-4 py-8">
        <!-- ═══════════════════════════════════════════════════════════════════════
             ENCABEZADO Y ACCIONES PRINCIPALES
             ═══════════════════════════════════════════════════════════════════════ -->
        <div class="mb-8">
            <!-- Título Principal -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-black dark:text-white">
                        Inventario de Equipos
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Gestiona los equipos de cómputo en todos los laboratorios
                    </p>
                </div>

                <!-- Botón de Acción Principal: Añadir Nuevo Equipo -->
                <router-link
                    v-if="authStore.isAdmin"
                    to="/equipment/create"
                    :class="primaryButtonClasses"
                >
                    <!-- Icono Plus -->
                    <svg
                        class="w-5 h-5 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Añadir Equipo
                </router-link>
            </div>

            <!-- Breadcrumb / Ruta de Navegación -->
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <router-link
                            to="/dashboard"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white"
                        >
                            Dashboard
                        </router-link>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg
                                class="w-4 h-4 text-gray-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Equipos
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════════
             TABLA DE DATOS (DataTable Component)
             ═══════════════════════════════════════════════════════════════════════ -->
        <DataTable
            :columns="columns"
            :items="equipment"
            :loading="loading"
            :error="error"
        >


            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Celda Personalizada - Tipo de Equipo
                 ═══════════════════════════════════════════════════════════════

                 Muestra el tipo de equipo con formateo capitalizado
                 ═══════════════════════════════════════════════════════════════ -->
            <template #cell-type="{ item }">
                <span class="text-sm text-gray-900 dark:text-gray-100 capitalize">
                    {{ formatEquipmentType(item.type) }}
                </span>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Celda Personalizada - Estado Operacional
                 ═══════════════════════════════════════════════════════════════

                 PRUEBA CLAVE: Renderizado condicional basado en boolean

                 Muestra un badge verde si is_operational es true
                 Muestra un badge rojo si is_operational es false
                 ═══════════════════════════════════════════════════════════════ -->
            <template #cell-is_operational="{ value }">
                <span :class="getOperationalBadgeClasses(value)">
                    <!-- Icono de estado -->
                    <svg
                        class="w-4 h-4 mr-1.5"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            v-if="value"
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                        <path
                            v-else
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ value ? 'Operacional' : 'Fuera de Servicio' }}
                </span>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Acciones por Fila
                 ═══════════════════════════════════════════════════════════════

                 Botones de acción para cada registro de equipo.
                 Se muestran diferentes botones según el rol del usuario.
                 ═══════════════════════════════════════════════════════════════ -->
            <template #actions="{ item }">
                <div class="flex items-center justify-end space-x-2">
          <!-- Ver -->
          <button
            @click="handleView(item)"
            class="rounded p-1 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:text-blue-400 dark:hover:bg-blue-900/30 dark:hover:text-blue-300 dark:focus:ring-offset-gray-800"
            title="Ver detalles"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>

          <!-- Editar (solo admin) -->
          <button
            v-if="authStore.isAdmin"
            @click="handleEdit(item)"
            class="rounded p-1 text-green-600 transition-colors hover:bg-green-50 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:text-green-400 dark:hover:bg-green-900/30 dark:hover:text-green-300 dark:focus:ring-offset-gray-800"
            title="Editar laboratorio"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>

          <!-- Eliminar (solo admin) -->
          <button
            v-if="authStore.isAdmin"
            @click="handleDelete(item)"
            class="rounded p-1 text-red-600 transition-colors hover:bg-red-50 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:text-red-400 dark:hover:bg-red-900/30 dark:hover:text-red-300 dark:focus:ring-offset-gray-800"
            title="Eliminar laboratorio"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Estado Vacío
                 ═══════════════════════════════════════════════════════════════

                 Se muestra cuando no hay equipos registrados en el sistema
                 ═══════════════════════════════════════════════════════════════ -->
            <template #empty>
                <div class="text-center py-12">
                    <!-- Icono Ilustrativo -->
                    <div class="flex justify-center mb-4">
                        <div class="w-24 h-24 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                            <svg
                                class="w-12 h-12 text-indigo-600 dark:text-indigo-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Mensaje Principal -->
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        No hay equipos registrados
                    </h3>

                    <!-- Mensaje Descriptivo -->
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Comienza agregando el primer equipo al inventario del sistema
                    </p>

                    <!-- Call to Action -->
                    <router-link
                        v-if="authStore.isAdmin"
                        to="/equipment/create"
                        :class="primaryButtonClasses"
                    >
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Añadir Primer Equipo
                    </router-link>
                </div>
            </template>
        </DataTable>
    </div>
</template>

<script setup>
// ═════════════════════════════════════════════════════════════════════════════
// IMPORTS
// ═════════════════════════════════════════════════════════════════════════════

// Vue Core
import { onMounted, computed } from 'vue';

// Vue Router
import { useRouter } from 'vue-router';

// Pinia Stores
import { useAuthStore } from '@/stores/auth';

// Componentes
import DataTable from '@/components/ui/DataTable.vue';

// Composables
import { useEquipment } from '@/composables/useEquipment';
import { useToast } from '@/composables/useToast';

// Librerías Externas
import Swal from 'sweetalert2';

// ═════════════════════════════════════════════════════════════════════════════
// INSTANCIACIÓN DE SERVICIOS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Router de Vue
 * Permite la navegación programática entre vistas
 */
const router = useRouter();

/**
 * Auth Store
 * Proporciona información sobre el usuario autenticado y sus permisos
 */
const authStore = useAuthStore();

/**
 * Composable de Equipment
 * Extrae todo el estado reactivo y los métodos para gestionar equipment
 */
const { equipment, loading, error, fetchEquipment, deleteEquipment } = useEquipment();

/**
 * Composable de Toast
 * Sistema de notificaciones profesional
 */
const toast = useToast();

// ═════════════════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE COLUMNAS DEL DATATABLE
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Definición de columnas para el DataTable
 *
 * CARACTERÍSTICA CLAVE: Soporte para Dot Notation
 * La columna 'lab.name' demuestra la capacidad del DataTable para
 * resolver propiedades anidadas automáticamente usando la función
 * getNestedValue del componente DataTable.
 *
 * Cada columna especifica:
 * - key: La propiedad del objeto que se mostrará (soporta dot notation)
 * - label: El encabezado visible de la columna
 *
 * NOTA: Las columnas con slots personalizados (cell-identifier, cell-is_operational)
 * se renderizan usando los templates definidos en la sección de slots del DataTable
 */
const columns = [
    {
        key: 'identifier',
        label: 'Identificador'
    },
    {
        key: 'type',
        label: 'Tipo'
    },
    {
        key: 'lab.name',
        label: 'Laboratorio'
    },
    {
        key: 'is_operational',
        label: 'Estado'
    }
];

// ═════════════════════════════════════════════════════════════════════════════
// COMPUTED PROPERTIES & HELPERS
// ═════════════════════════════════════════════════════════════════════════════



/**
 * Clases CSS para el botón primario
 */
const primaryButtonClasses = computed(() =>
    'inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
);



/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Helper: Obtener clases para badge de estado operacional
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * CARACTERÍSTICA CLAVE: Renderizado Condicional Avanzado
 *
 * Retorna las clases CSS apropiadas según el estado operacional del equipo.
 * Verde con check icon si está operacional, rojo con X icon si no lo está.
 *
 * @param {Boolean} isOperational - Estado operacional del equipo
 * @returns {String} Clases CSS para el badge
 */
const getOperationalBadgeClasses = (isOperational) => {
    const baseClasses = 'inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold';

    if (isOperational) {
        return `${baseClasses} bg-green-100 text-green-800 border border-green-200`;
    } else {
        return `${baseClasses} bg-red-100 text-red-800 border border-red-200`;
    }
};

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Helper: Formatear tipo de equipo
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Convierte el tipo de equipo en formato legible (capitalizado y con espacios).
 *
 * Ejemplos:
 * - "desktop" → "Desktop"
 * - "laptop" → "Laptop"
 * - "workstation" → "Workstation"
 *
 * @param {String} type - Tipo de equipo en formato raw
 * @returns {String} Tipo formateado
 */
const formatEquipmentType = (type) => {
    if (!type) return 'N/A';

    // Capitalizar primera letra
    return type.charAt(0).toUpperCase() + type.slice(1).toLowerCase();
};

// ═════════════════════════════════════════════════════════════════════════════
// LIFECYCLE HOOKS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Hook onMounted
 *
 * Se ejecuta cuando el componente ha sido montado en el DOM.
 * Aquí cargamos los datos iniciales de equipment desde la API.
 */
onMounted(() => {
    fetchEquipment();
});

// ═════════════════════════════════════════════════════════════════════════════
// MANEJADORES DE EVENTOS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador: Ver Detalles de Equipment
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Navega a la vista de detalles del equipment seleccionado.
 *
 * @param {Object} item - El objeto equipment seleccionado
 */
const handleView = (item) => {

    router.push({
        name: 'equipment.show',
        params: { id: item.id }
    });
};

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador: Editar Equipment
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Navega a la vista de edición del equipment seleccionado.
 *
 * PERMISOS: Solo accesible por administradores
 *
 * @param {Object} item - El objeto equipment seleccionado
 */
const handleEdit = (item) => {

    router.push({
        name: 'equipment.edit',
        params: { id: item.id }
    });
};

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador: Eliminar Equipment
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Muestra un diálogo de confirmación profesional antes de eliminar el equipment.
 *
 * PERMISOS: Solo accesible por administradores
 *
 * CARACTERÍSTICAS:
 * - SweetAlert2 para confirmación visual
 * - Validación de reservas activas en el backend
 * - Toast notifications para feedback
 * - Actualización automática de la lista
 *
 * @param {Object} item - El objeto equipment seleccionado
 */
const handleDelete = async (item) => {

    // Mostrar diálogo de confirmación con SweetAlert2
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        html: `Se eliminará el equipo <strong>"${item.identifier}"</strong> del laboratorio <strong>"${item.lab?.name || 'N/A'}"</strong>.<br>Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true
    });

    // Si el usuario cancela, no hacer nada
    if (!result.isConfirmed) {
        return;
    }

    try {
        await deleteEquipment(item.id);

        // Mostrar notificación de éxito
        toast.success(`Equipo "${item.identifier}" eliminado exitosamente`);
    } catch (err) {
        console.error(' Error al eliminar equipo:', err);

        // Mostrar notificación de error con el mensaje del backend
        const errorMessage = err.response?.data?.message ||
                           'Error al eliminar el equipo. Por favor, intenta nuevamente.';
        toast.error(errorMessage);
    }
};
</script>

<style scoped>
/**
 * ═════════════════════════════════════════════════════════════════════════════
 * ESTILOS ESPECÍFICOS DEL COMPONENTE
 * ═════════════════════════════════════════════════════════════════════════════
 *
 * Este componente utiliza principalmente clases de Tailwind CSS.
 * Los estilos personalizados adicionales se pueden agregar aquí si es necesario.
 */

/* Animación suave para transiciones de hover */
button,
a {
    transition: all 0.2s ease-in-out;
}

/* Mejora visual para el estado de focus */
button:focus,
a:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
</style>
