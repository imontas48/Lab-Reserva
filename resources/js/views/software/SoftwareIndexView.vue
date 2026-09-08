<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         VISTA: Catálogo de Software
         ═══════════════════════════════════════════════════════════════════════════

         PROPÓSITO:
         Vista principal para listar todos los software registrados en el sistema.
         Permite visualizar, editar y eliminar registros de software.

         ARQUITECTURA:
         Esta vista implementa el patrón "Composable + Vista + Componente Reutilizable":
         - useSoftware: Maneja la lógica de datos
         - SoftwareIndexView: Orquesta la presentación
         - DataTable: Renderiza los datos de forma consistente

         ═══════════════════════════════════════════════════════════════════════════ -->

    <div class="container mx-auto px-4 py-8">
        <!-- ═══════════════════════════════════════════════════════════════════════
             ENCABEZADO Y ACCIONES PRINCIPALES
             ═══════════════════════════════════════════════════════════════════════ -->
        <div class="mb-8">
            <!-- Título Principal -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Catálogo de Software
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Gestiona el software disponible en los laboratorios
                    </p>
                </div>

                <!-- Botón de Acción Principal: Crear Nuevo Software -->
                <router-link
                    v-if="authStore.isAdmin"
                    to="/software/create"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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
                    Agregar Software
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
                                Software
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
            :items="software"
            :loading="loading"
            :error="error"
        >
            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Celda Personalizada - Nombre del Software
                 ═══════════════════════════════════════════════════════════════

                 Renderiza el nombre del software con un icono visual
                 ═══════════════════════════════════════════════════════════════ -->
            <template #cell-name="{ item }">
                <div class="flex items-center">
                    <!-- Icono de Software (CPU/Chip simplificado) -->
                    <div :class="iconWrapperClasses">
                        <svg
                            :class="iconClasses"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"
                            />
                        </svg>
                    </div>

                    <!-- Información del Software -->
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ item.name }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            ID: {{ item.id }}
                        </div>
                    </div>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Celda Personalizada - Versión
                 ═══════════════════════════════════════════════════════════════

                 Muestra la versión del software con un badge visual
                 ═══════════════════════════════════════════════════════════════ -->
            <template #cell-version="{ item }">
                <span :class="badgeClasses">
                    v{{ item.version }}
                </span>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Celda Personalizada - Equipos Instalados
                 ═══════════════════════════════════════════════════════════════

                 Muestra el número de equipos que tienen instalado este software
                 ═══════════════════════════════════════════════════════════════ -->
            <template #cell-equipment_count="{ item }">
                <div class="flex items-center">
                    <!-- Icono de Computadora (Monitor simplificado) -->
                    <svg
                        class="w-4 h-4 mr-2 text-gray-400"
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

                    <!-- Contador Reactivo -->
                    <span :class="getEquipmentCountClasses(item.equipment_count)">
                        {{ item.equipment_count || 0 }}
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                            {{ item.equipment_count === 1 ? 'equipo' : 'equipos' }}
                        </span>
                    </span>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Acciones por Fila
                 ═══════════════════════════════════════════════════════════════

                 Botones de acción para cada registro de software.
                 Se muestran diferentes botones según el rol del usuario.

                 NOTA: Los SVG paths se mantienen porque son la definición
                 estándar de Heroicons y cambian raramente. Extraerlos a
                 componentes separados sería over-engineering en este caso.
                 ═══════════════════════════════════════════════════════════════ -->
            <template #actions="{ item }">
                <div class="flex items-center justify-end gap-2">
                    <!-- Botón: Ver Detalles -->
                    <button
                        @click="handleView(item)"
                        :class="buttonClasses.view"
                        title="Ver detalles"
                    >
                        <svg
                            class="w-4 h-4 mr-1.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                        Ver
                    </button>

                    <!-- Botón: Editar (Solo Administradores) -->
                    <button
                        v-if="authStore.isAdmin"
                        @click="handleEdit(item)"
                        :class="buttonClasses.edit"
                        title="Editar software"
                    >
                        <svg
                            class="w-4 h-4 mr-1.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                        Editar
                    </button>

                    <!-- Botón: Eliminar (Solo Administradores) -->
                    <button
                        v-if="authStore.isAdmin"
                        @click="handleDelete(item)"
                        :class="buttonClasses.delete"
                        title="Eliminar software"
                    >
                        <svg
                            class="w-4 h-4 mr-1.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                        Eliminar
                    </button>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════
                 SLOT: Estado Vacío
                 ═══════════════════════════════════════════════════════════════

                 Se muestra cuando no hay software registrado en el sistema
                 ═══════════════════════════════════════════════════════════════ -->
            <template #empty>
                <div class="text-center py-12">
                    <!-- Icono Ilustrativo -->
                    <div class="flex justify-center mb-4">
                        <div class="w-24 h-24 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                            <svg
                                class="w-12 h-12 text-purple-600 dark:text-purple-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Mensaje Principal -->
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        No hay software registrado
                    </h3>

                    <!-- Mensaje Descriptivo -->
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Comienza agregando el primer software al catálogo del sistema
                    </p>

                    <!-- Call to Action -->
                    <router-link
                        v-if="authStore.isAdmin"
                        to="/software/create"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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
                        Agregar Primer Software
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
import { useSoftware } from '@/composables/useSoftware';
import { confirmDestructive } from '@/utils/confirm';
import { useToast } from '@/composables/useToast';

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
 * Composable de Software
 * Extrae todo el estado reactivo y los métodos para gestionar software
 */
const { software, loading, error, fetchSoftware, deleteSoftware } = useSoftware();
const toast = useToast();

// ═════════════════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE COLUMNAS DEL DATATABLE
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Definición de columnas para el DataTable
 *
 * Cada columna especifica:
 * - key: La propiedad del objeto que se mostrará
 * - label: El encabezado visible de la columna
 *
 * NOTA: Las columnas con slots personalizados (cell-name, cell-version, etc.)
 * se renderizan usando los templates definidos en la sección de slots del DataTable
 */
const columns = [
    {
        key: 'name',
        label: 'Nombre'
    },
    {
        key: 'version',
        label: 'Versión'
    },
    {
        key: 'equipment_count',
        label: 'Equipos Instalados'
    }
];

// ═════════════════════════════════════════════════════════════════════════════
// COMPUTED PROPERTIES & HELPERS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Clases CSS para el icono wrapper
 * Centraliza las clases de Tailwind para evitar repetición
 */
const iconWrapperClasses = computed(() =>
    'flex-shrink-0 w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3'
);

/**
 * Clases CSS para los iconos SVG
 */
const iconClasses = computed(() =>
    'w-6 h-6 text-purple-600 dark:text-purple-400'
);

/**
 * Clases CSS para badges de versión
 */
const badgeClasses = computed(() =>
    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
);

/**
 * Clases CSS para los botones de acción
 * Centralizadas para evitar repetición y facilitar cambios globales
 */
const buttonClasses = {
    view: 'inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
    edit: 'inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500',
    delete: 'inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500'
};

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Helper: Obtener clases para el contador de equipos
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Retorna las clases CSS apropiadas según el número de equipos.
 * Verde si hay equipos instalados, gris si no hay ninguno.
 *
 * @param {Number} count - Número de equipos
 * @returns {String} Clases CSS
 */
const getEquipmentCountClasses = (count) => {
    const baseClasses = 'font-medium text-sm';
    const colorClasses = count > 0
        ? 'text-green-600 dark:text-green-400'
        : 'text-gray-400 dark:text-gray-500';

    return `${baseClasses} ${colorClasses}`;
};

// ═════════════════════════════════════════════════════════════════════════════
// LIFECYCLE HOOKS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Hook onMounted
 *
 * Se ejecuta cuando el componente ha sido montado en el DOM.
 * Aquí cargamos los datos iniciales de software desde la API.
 */
onMounted(() => {
    fetchSoftware();
});

// ═════════════════════════════════════════════════════════════════════════════
// MANEJADORES DE EVENTOS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador: Ver Detalles de Software
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Navega a la vista de detalles del software seleccionado.
 *
 * @param {Object} item - El objeto software seleccionado
 */
const handleView = (item) => {

    router.push({
        name: 'software.show',
        params: { id: item.id }
    });
};

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador: Editar Software
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Navega a la vista de edición del software seleccionado.
 *
 * PERMISOS: Solo accesible por administradores
 *
 * @param {Object} item - El objeto software seleccionado
 */
const handleEdit = (item) => {

    router.push({
        name: 'software.edit',
        params: { id: item.id }
    });
};

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador: Eliminar Software
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Muestra un diálogo de confirmación antes de eliminar el software.
 *
 * PERMISOS: Solo accesible por administradores
 *
 * TODO: Implementar la lógica de eliminación con:
 * - Modal de confirmación
 * - Llamada al método deleteSoftware del composable
 * - Notificación de éxito/error
 * - Actualización de la lista
 *
 * @param {Object} item - El objeto software seleccionado
 */
const handleDelete = async (item) => {
    // Era un stub: confirm() nativo y un alert('pendiente de implementar'),
    // aunque deleteSoftware ya estaba implementado en el composable.
    const confirmed = await confirmDestructive({
        html: `Se eliminará el software <strong>"${item.name}"</strong>.<br>`
            + 'Dejará de figurar en los equipos que lo tengan instalado.',
    });

    if (!confirmed) {
        return;
    }

    try {
        await deleteSoftware(item.id);
        toast.success(`Software "${item.name}" eliminado`);
    } catch {
        // El composable lanza siempre y deja el motivo en error.value.
        toast.error(error.value ?? 'No se pudo eliminar el software.');
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
