<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         VISTA: SoftwareCreateEditView (Formulario Unificado)
         ═══════════════════════════════════════════════════════════════════════════

         PROPÓSITO:
         Vista unificada para crear y editar software. Este componente maneja
         ambos modos (create/edit) reutilizando la misma UI y lógica.

         CARACTERÍSTICAS:
         - ✅ Modo dual: Crear/Editar basado en route.params.id
         - ✅ Validación en tiempo real con BaseInput
         - ✅ Manejo de errores de validación (422)
         - ✅ Estados de carga visual
         - ✅ Navegación automática después del éxito
         - ✅ Composable pattern con useSoftware

         RUTAS:
         - /software/create → Modo creación
         - /software/:id/edit → Modo edición

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
                        :to="{ name: 'software.index' }"
                        class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                    >
                        Software
                    </router-link>
                    <span>/</span>
                    <span class="text-gray-900 dark:text-white">
                        {{ isEditing ? 'Editar' : 'Crear' }}
                    </span>
                </nav>

                <!-- Título dinámico -->
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ isEditing ? 'Editar Software' : 'Añadir Nuevo Software' }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ isEditing
                        ? 'Modifica los datos del software existente'
                        : 'Completa el formulario para agregar nuevo software al catálogo'
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
                        @click="loadSoftwareData"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        Reintentar
                    </button>
                    <router-link
                        :to="{ name: 'software.index' }"
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
                     CAMPO: Nombre del Software
                     ───────────────────────────────────────────────────────────── -->
                <BaseInput
                    v-model="form.name"
                    label="Nombre del Software"
                    type="text"
                    placeholder="Ej: Adobe Photoshop, Microsoft Office, AutoCAD"
                    :error="getFieldError('name')"
                    required
                />

                <!-- ─────────────────────────────────────────────────────────────
                     CAMPO: Versión
                     ───────────────────────────────────────────────────────────── -->
                <BaseInput
                    v-model="form.version"
                    label="Versión"
                    type="text"
                    placeholder="Ej: 2024, v15.3, CC 2024"
                    :error="getFieldError('version')"
                    required
                />

                <!-- ═════════════════════════════════════════════════════════════
                     BOTONES DE ACCIÓN
                     ═════════════════════════════════════════════════════════════ -->
                <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <!-- Botón Cancelar -->
                    <router-link
                        :to="{ name: 'software.index' }"
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
                                    : 'Crear Software'
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
import { useSoftware } from '@/composables/useSoftware';
import BaseInput from '@/components/forms/BaseInput.vue';

// ═════════════════════════════════════════════════════════════════════════════
// COMPOSABLES
// ═════════════════════════════════════════════════════════════════════════════

const route = useRoute();
const router = useRouter();
const {
    loading,
    error,
    validationErrors,
    fetchSoftwareById,
    createSoftware,
    updateSoftware,
    clearErrors
} = useSoftware();

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
    version: ''
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
 * Cargar datos del software (modo edición)
 * Se ejecuta en onMounted si estamos en modo edición
 */
const loadSoftwareData = async () => {
    if (!isEditing.value) return;

    try {
        initialLoading.value = true;
        loadError.value = null;

        console.log('🔍 Cargando datos del software para edición...');
        const software = await fetchSoftwareById(route.params.id);

        // Poblar el formulario con los datos obtenidos
        form.value = {
            name: software.name || '',
            version: software.version || ''
        };

        console.log('✅ Datos cargados en el formulario:', form.value);
    } catch (err) {
        console.error('❌ Error al cargar datos del software:', err);
        loadError.value = err.response?.data?.message || 'No se pudieron cargar los datos del software';
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

        console.log('📤 Enviando formulario...', {
            mode: isEditing.value ? 'edit' : 'create',
            data: form.value
        });

        let result;

        if (isEditing.value) {
            // Modo edición: actualizar software existente
            console.log(`📝 Actualizando software ${route.params.id}...`);
            result = await updateSoftware(route.params.id, form.value);
        } else {
            // Modo creación: crear nuevo software
            console.log('✨ Creando nuevo software...');
            result = await createSoftware(form.value);
        }

        console.log('✅ Operación exitosa:', result);

        // Redirigir al listado de software
        router.push({
            name: 'software.index',
            // Podríamos agregar un query param para mostrar un mensaje de éxito
            query: {
                success: isEditing.value ? 'updated' : 'created',
                name: result.name
            }
        });
    } catch (err) {
        console.error('❌ Error al enviar el formulario:', err);

        // Los errores de validación ya están manejados en el composable
        // y se mostrarán automáticamente en los BaseInput correspondientes

        // Si no es un error de validación (422), scroll al tope para ver el mensaje
        if (err.response?.status !== 422) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
};

// ═════════════════════════════════════════════════════════════════════════════
// LIFECYCLE HOOKS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Al montar el componente:
 * - Si estamos en modo edición, cargar los datos del software
 * - Si estamos en modo creación, el formulario ya está limpio
 */
onMounted(() => {
    console.log('🎬 SoftwareCreateEditView montado', {
        mode: isEditing.value ? 'edit' : 'create',
        softwareId: route.params.id || 'N/A'
    });

    if (isEditing.value) {
        loadSoftwareData();
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
