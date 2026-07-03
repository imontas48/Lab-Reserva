<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         VISTA: LabsCreateEditView (Formulario Unificado)
         ═══════════════════════════════════════════════════════════════════════════

         PROPÓSITO:
         Vista unificada para crear y editar laboratorios. Este componente maneja
         ambos modos (create/edit) reutilizando la misma UI y lógica.

         CARACTERÍSTICAS:
         -  Modo dual: Crear/Editar basado en route.params.id
         -  Validación en tiempo real con BaseInput
         -  Manejo de errores de validación (422)
         -  Estados de carga visual
         -  Navegación automática después del éxito
         -  Composable pattern con useLabs

         RUTAS:
         - /labs/create → Modo creación
         - /labs/:id/edit → Modo edición

         ═══════════════════════════════════════════════════════════════════════════ -->

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">

            <!-- ═════════════════════════════════════════════════════════════════
                 ENCABEZADO
                 ═════════════════════════════════════════════════════════════════ -->
            <div class="mb-8">
                <!-- Breadcrumb de navegación -->
                <nav class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <router-link
                        :to="{ name: 'dashboard' }"
                        class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                    >
                        Inicio
                    </router-link>
                    <span>/</span>
                    <router-link
                        :to="{ name: 'labs.index' }"
                        class="hover:text-indigo-600 transition-colors"
                    >
                        Laboratorios
                    </router-link>
                    <span>/</span>
                    <span class="text-gray-900 dark:text-white">
                        {{ isEditing ? 'Editar' : 'Crear' }}
                    </span>
                </nav>

                <!-- Título dinámico -->
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ isEditing ? 'Editar Laboratorio' : 'Crear Nuevo Laboratorio' }}
                </h1>
                <p class="text-gray-600 dark:text-gray-300">
                    {{ isEditing
                        ? 'Modifica los datos del laboratorio existente'
                        : 'Completa el formulario para agregar un nuevo laboratorio al sistema'
                    }}
                </p>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════
                 ESTADO DE CARGA INICIAL (Modo Edición)
                 ═════════════════════════════════════════════════════════════════ -->
            <div
                v-if="initialLoading"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8"
            >
                <div class="animate-pulse space-y-6">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                    <div class="h-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════
                 ERROR GENERAL (Error al cargar datos en modo edición)
                 ═════════════════════════════════════════════════════════════════ -->
            <div
                v-else-if="loadError"
                class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-6 rounded-r-lg mb-6"
            >
                <div class="flex items-start">
                    <svg
                        class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-0.5"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-red-800 dark:text-red-400 font-medium mb-1">
                            Error al cargar los datos
                        </h3>
                        <p class="text-red-700 dark:text-red-300 text-sm">
                            {{ loadError }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex gap-3">
                    <button
                        @click="loadLabData"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        Reintentar
                    </button>
                    <router-link
                        :to="{ name: 'labs.index' }"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        Volver al Listado
                    </router-link>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════
                 FORMULARIO PRINCIPAL
                 ═════════════════════════════════════════════════════════════════ -->
            <form
                v-else
                @submit.prevent="handleSubmit"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 space-y-6"
            >
                <!-- Error general del formulario -->
                <div
                    v-if="error"
                    class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-lg"
                >
                    <div class="flex items-center">
                        <svg
                            class="w-5 h-5 text-red-500 mr-2"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <p class="text-red-800 dark:text-red-400 text-sm font-medium">
                            {{ error }}
                        </p>
                    </div>
                </div>

                <!-- ─────────────────────────────────────────────────────────────
                     CAMPO: Nombre del Laboratorio
                     ───────────────────────────────────────────────────────────── -->
                <BaseInput
                    v-model="form.name"
                    label="Nombre del Laboratorio"
                    type="text"
                    placeholder="Ej: Laboratorio de Redes Avanzadas"
                    :error="getFieldError('name')"
                    required
                />

                <!-- ─────────────────────────────────────────────────────────────
                     CAMPO: Ubicación
                     ───────────────────────────────────────────────────────────── -->
                <BaseInput
                    v-model="form.location"
                    label="Ubicación"
                    type="text"
                    placeholder="Ej: Edificio C, Piso 2, Aula 201"
                    :error="getFieldError('location')"
                    required
                />

                <!-- ─────────────────────────────────────────────────────────────
                     CAMPO: Capacidad
                     ───────────────────────────────────────────────────────────── -->
                <BaseInput
                    v-model.number="form.capacity"
                    label="Capacidad (Número de Estudiantes)"
                    type="number"
                    placeholder="Ej: 30"
                    :error="getFieldError('capacity')"
                    required
                />

                <!-- ─────────────────────────────────────────────────────────────
                     CAMPO: Descripción (Textarea Simulado con BaseInput)
                     ───────────────────────────────────────────────────────────── -->
                <div>
                    <label
                        for="description"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Descripción
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        :class="[
                            getFieldError('description')
                                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                : 'border-gray-300 dark:border-gray-600'
                        ]"
                        placeholder="Describe las características del laboratorio, equipamiento disponible, horarios de acceso, etc."
                    />

                    <!-- Error de validación para descripción -->
                    <transition name="error-fade">
                        <p
                            v-if="getFieldError('description')"
                            class="mt-2 text-sm text-red-600 dark:text-red-400"
                            role="alert"
                        >
                            {{ getFieldError('description') }}
                        </p>
                    </transition>
                </div>

                <!-- ═════════════════════════════════════════════════════════════
                     BOTONES DE ACCIÓN
                     ═════════════════════════════════════════════════════════════ -->
                <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <!-- Botón Cancelar -->
                    <router-link
                        :to="{ name: 'labs.index' }"
                        class="px-6 py-2.5 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium rounded-lg transition-colors"
                    >
                        Cancelar
                    </router-link>

                    <!-- Botón Enviar -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors flex items-center gap-2"
                    >
                        <!-- Spinner de carga -->
                        <svg
                            v-if="loading"
                            class="animate-spin h-5 w-5 text-white"
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
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>

                        <!-- Texto del botón -->
                        <span>
                            {{ loading
                                ? 'Guardando...'
                                : isEditing
                                    ? 'Guardar Cambios'
                                    : 'Crear Laboratorio'
                            }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useLabs } from '@/composables/useLabs';
import { useToast } from '@/composables/useToast';
import BaseInput from '@/components/forms/BaseInput.vue';

// ═════════════════════════════════════════════════════════════════════════════
// COMPOSABLES
// ═════════════════════════════════════════════════════════════════════════════

const route = useRoute();
const router = useRouter();
const toast = useToast();
const {
    loading,
    error,
    validationErrors,
    fetchLabById,
    createLab,
    updateLab,
    clearErrors
} = useLabs();

// ═════════════════════════════════════════════════════════════════════════════
// ESTADO REACTIVO
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Determina si estamos en modo edición o creación
 * @type {ComputedRef<Boolean>}
 */
const isEditing = computed(() => !!route.params.id);

/**
 * Estado de carga inicial (para cargar datos en modo edición)
 * @type {Ref<Boolean>}
 */
const initialLoading = ref(false);

/**
 * Error al cargar datos (modo edición)
 * @type {Ref<String|null>}
 */
const loadError = ref(null);

/**
 * Datos del formulario
 * @type {Ref<Object>}
 */
const form = ref({
    name: '',
    location: '',
    capacity: null,
    description: ''
});

// ═════════════════════════════════════════════════════════════════════════════
// MÉTODOS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Obtiene el primer mensaje de error para un campo específico
 * Los errores de validación vienen del backend en formato:
 * { field_name: ['Error message 1', 'Error message 2'] }
 *
 * @param {String} fieldName - Nombre del campo
 * @returns {String} - Mensaje de error o cadena vacía
 */
const getFieldError = (fieldName) => {
    if (!validationErrors.value[fieldName]) return '';

    const errors = validationErrors.value[fieldName];
    return Array.isArray(errors) ? errors[0] : errors;
};

/**
 * Cargar datos del laboratorio (modo edición)
 * Se ejecuta en onMounted si estamos en modo edición
 */
const loadLabData = async () => {
    if (!isEditing.value) return;

    try {
        initialLoading.value = true;
        loadError.value = null;

        console.log(' Cargando datos del laboratorio para edición...');
        const lab = await fetchLabById(route.params.id);

        // Poblar el formulario con los datos obtenidos
        form.value = {
            name: lab.name || '',
            location: lab.location || '',
            capacity: lab.capacity || null,
            description: lab.description || ''
        };

        console.log(' Datos cargados en el formulario:', form.value);
    } catch (err) {
        console.error(' Error al cargar datos del laboratorio:', err);
        loadError.value = err.response?.data?.message || 'No se pudieron cargar los datos del laboratorio';
    } finally {
        initialLoading.value = false;
    }
};

/**
 * Manejar el envío del formulario
 * Determina si es creación o edición y ejecuta la acción correspondiente
 */
const handleSubmit = async () => {
    try {
        // Limpiar errores previos
        clearErrors();

        console.log(' Enviando formulario...', {
            mode: isEditing.value ? 'edit' : 'create',
            data: form.value
        });

        let result;

        if (isEditing.value) {
            // Modo edición: actualizar laboratorio existente
            console.log(` Actualizando laboratorio ${route.params.id}...`);
            result = await updateLab(route.params.id, form.value);

            // Notificación de éxito
            toast.success(`Laboratorio "${result.name}" actualizado exitosamente`);
        } else {
            // Modo creación: crear nuevo laboratorio
            console.log(' Creando nuevo laboratorio...');
            result = await createLab(form.value);

            // Notificación de éxito
            toast.success(`Laboratorio "${result.name}" creado exitosamente`);
        }

        console.log(' Operación exitosa:', result);

        // Redirigir al listado de laboratorios
        router.push({ name: 'labs.index' });
    } catch (err) {
        console.error(' Error al enviar el formulario:', err);

        // Notificación de error si no es validación
        if (err.response?.status !== 422) {
            const errorMessage = err.response?.data?.message ||
                                `Error al ${isEditing.value ? 'actualizar' : 'crear'} el laboratorio`;
            toast.error(errorMessage);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            // Para errores de validación, mostrar mensaje genérico
            toast.warning('Por favor, corrige los errores en el formulario');
        }
    }
};

// ═════════════════════════════════════════════════════════════════════════════
// LIFECYCLE HOOKS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Al montar el componente:
 * - Si estamos en modo edición, cargar los datos del laboratorio
 * - Si estamos en modo creación, el formulario ya está limpio
 */
onMounted(() => {
    console.log(' LabsCreateEditView montado', {
        mode: isEditing.value ? 'edit' : 'create',
        labId: route.params.id || 'N/A'
    });

    //  FIX: Limpiar estado del composable al montar
    // El composable es compartido entre vistas, así que limpiamos
    // cualquier error o estado de carga previo
    clearErrors();

    if (isEditing.value) {
        loadLabData();
    }
});
</script>

<style scoped>
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ANIMACIÓN: Error Fade
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Transición suave para los mensajes de error.
 */
.error-fade-enter-active,
.error-fade-leave-active {
    transition: all 0.2s ease;
}

.error-fade-enter-from {
    opacity: 0;
    transform: translateY(-4px);
}

.error-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
