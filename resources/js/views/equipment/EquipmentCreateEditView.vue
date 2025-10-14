<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         VISTA UNIFICADA: CREAR/EDITAR EQUIPMENT
         ═══════════════════════════════════════════════════════════════════════════

         Esta vista es la CULMINACIÓN de nuestro sistema de formularios.
         Integra múltiples composables, BaseInput y BaseSelect en una interfaz
         robusta y elegante.

         CARACTERÍSTICAS CLAVE:
         - Modo dual (crear/editar) con detección automática
         - Múltiples composables (useEquipment + useLabs)
         - Integración de BaseInput para campos de texto
         - Integración de BaseSelect para relaciones (lab_id)
         - Manejo completo de validación Laravel (422)
         - Estados de carga con skeleton loaders
         - Manejo robusto de errores

         ═══════════════════════════════════════════════════════════════════════════ -->

    <div class="container mx-auto px-4 py-8">
        <!-- ─────────────────────────────────────────────────────────────────
             ENCABEZADO CON NAVEGACIÓN
             ───────────────────────────────────────────────────────────────── -->
        <div class="mb-8">
            <!-- Breadcrumbs -->
            <nav class="mb-4 flex items-center text-sm text-gray-600 dark:text-gray-400">
                <router-link
                    to="/dashboard"
                    class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                    Inicio
                </router-link>
                <span class="mx-2">/</span>
                <router-link
                    to="/equipment"
                    class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                    Equipos
                </router-link>
                <span class="mx-2">/</span>
                <span class="text-gray-900 dark:text-white font-medium">
                    {{ isEditing ? 'Editar' : 'Nuevo' }}
                </span>
            </nav>

            <!-- Título dinámico -->
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ isEditing ? 'Editar Equipo' : 'Añadir Nuevo Equipo' }}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ isEditing
                    ? 'Modifica los datos del equipo en el formulario a continuación'
                    : 'Completa el formulario para registrar un nuevo equipo en el sistema'
                }}
            </p>
        </div>

        <!-- ─────────────────────────────────────────────────────────────────
             CONTENEDOR PRINCIPAL DEL FORMULARIO
             ───────────────────────────────────────────────────────────────── -->
        <div class="max-w-3xl">
            <!-- ESTADO: Cargando datos iniciales (solo en modo edición) -->
            <div v-if="isLoadingInitialData" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
                <div class="animate-pulse space-y-6">
                    <!-- Skeleton: Título de sección -->
                    <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>

                    <!-- Skeleton: Campos del formulario -->
                    <div class="space-y-4">
                        <div class="h-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>

                    <!-- Skeleton: Botón -->
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
                </div>
            </div>

            <!-- ESTADO: Formulario listo -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
                <!-- Banner de error general -->
                <div
                    v-if="equipmentError"
                    class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"
                >
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-400">
                                Error al {{ isEditing ? 'actualizar' : 'crear' }} el equipo
                            </h3>
                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                {{ equipmentError }}
                            </p>
                            <button
                                @click="clearErrors"
                                class="mt-2 text-sm text-red-600 dark:text-red-400 hover:text-red-500 dark:hover:text-red-300 font-medium"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <form @submit.prevent="handleSubmit" class="space-y-8">
                    <!-- ═════════════════════════════════════════════════════════
                         SECCIÓN 1: INFORMACIÓN BÁSICA
                         ═════════════════════════════════════════════════════════ -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                            Información Básica
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Campo: Identificador -->
                            <BaseInput
                                v-model="form.identifier"
                                label="Identificador del Equipo"
                                type="text"
                                placeholder="Ej: LAB-PC-001"
                                :error="getFieldError('identifier')"
                                required
                            />

                            <!-- Campo: Tipo -->
                            <BaseInput
                                v-model="form.type"
                                label="Tipo de Equipo"
                                type="text"
                                placeholder="Ej: Desktop, Laptop, Server"
                                :error="getFieldError('type')"
                                required
                            />
                        </div>
                    </div>

                    <!-- ═════════════════════════════════════════════════════════
                         SECCIÓN 2: ASIGNACIÓN Y RELACIONES
                         ═════════════════════════════════════════════════════════ -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                            </svg>
                            Asignación
                        </h2>

                        <!-- Campo: Laboratorio (BaseSelect - LA PRUEBA CLAVE) -->
                        <BaseSelect
                            v-model="form.lab_id"
                            label="Laboratorio Asignado"
                            :options="labOptions"
                            placeholder="Seleccione el laboratorio donde se ubicará el equipo..."
                            :error="getFieldError('lab_id')"
                            :disabled="isLoadingLabs"
                            required
                        />

                        <!-- Indicador de carga de laboratorios -->
                        <p v-if="isLoadingLabs" class="mt-2 text-sm text-gray-500 dark:text-gray-400 flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cargando laboratorios disponibles...
                        </p>

                        <!-- Mensaje si no hay laboratorios -->
                        <p v-if="!isLoadingLabs && labOptions.length === 0" class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                            ⚠️ No hay laboratorios disponibles.
                            <router-link to="/labs/create" class="underline hover:text-amber-700 dark:hover:text-amber-300">
                                Crea uno primero
                            </router-link>
                        </p>
                    </div>

                    <!-- ═════════════════════════════════════════════════════════
                         SECCIÓN 3: DETALLES TÉCNICOS
                         ═════════════════════════════════════════════════════════ -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                            </svg>
                            Especificaciones Técnicas
                        </h2>

                        <!-- Campo: Especificaciones (Textarea simulado con input) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Especificaciones
                                <span class="text-gray-500 dark:text-gray-400 font-normal ml-1">(Opcional)</span>
                            </label>
                            <textarea
                                v-model="form.specifications"
                                rows="4"
                                class="w-full px-4 py-2 border rounded-lg transition-colors
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                       dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:placeholder-gray-400"
                                :class="{
                                    'border-gray-300 dark:border-gray-600': !getFieldError('specifications'),
                                    'border-red-500 dark:border-red-500': getFieldError('specifications')
                                }"
                                placeholder="Ej: Intel Core i7-12700K, 32GB DDR4 RAM, 1TB NVMe SSD, NVIDIA RTX 3060..."
                            ></textarea>
                            <p v-if="getFieldError('specifications')" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ getFieldError('specifications') }}
                            </p>
                        </div>
                    </div>

                    <!-- ═════════════════════════════════════════════════════════
                         SECCIÓN 4: ESTADO OPERACIONAL
                         ═════════════════════════════════════════════════════════ -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Estado Operacional
                        </h2>

                        <!-- Campo: Estado Operacional (BaseSelect) -->
                        <BaseSelect
                            v-model="form.is_operational"
                            label="Estado del Equipo"
                            :options="operationalStatusOptions"
                            placeholder="Seleccione el estado actual del equipo..."
                            :error="getFieldError('is_operational')"
                            required
                        />
                    </div>

                    <!-- ═════════════════════════════════════════════════════════
                         ACCIONES DEL FORMULARIO
                         ═════════════════════════════════════════════════════════ -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                        <!-- Botón: Cancelar -->
                        <router-link
                            to="/equipment"
                            class="px-6 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700
                                   border border-gray-300 dark:border-gray-600 rounded-lg
                                   hover:bg-gray-50 dark:hover:bg-gray-600
                                   transition-colors font-medium"
                        >
                            Cancelar
                        </router-link>

                        <!-- Botón: Guardar -->
                        <button
                            type="submit"
                            :disabled="isSubmitting || isLoadingLabs || labOptions.length === 0"
                            class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg
                                   hover:bg-indigo-700 focus:outline-none focus:ring-2
                                   focus:ring-indigo-500 focus:ring-offset-2
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   transition-all duration-200
                                   flex items-center"
                        >
                            <!-- Spinner de carga -->
                            <svg
                                v-if="isSubmitting"
                                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <!-- Texto dinámico del botón -->
                            <span v-if="isSubmitting">
                                {{ isEditing ? 'Actualizando...' : 'Creando...' }}
                            </span>
                            <span v-else>
                                {{ isEditing ? 'Actualizar Equipo' : 'Crear Equipo' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useEquipment } from '@/composables/useEquipment';
import { useLabs } from '@/composables/useLabs';
import BaseInput from '@/components/forms/BaseInput.vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * COMPOSABLES - LA ORQUESTACIÓN DE MÚLTIPLES FUENTES DE DATOS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Esta vista es única porque coordina DOS composables diferentes:
 * 1. useEquipment: Para la gestión principal del formulario (CRUD)
 * 2. useLabs: Para obtener la lista de laboratorios disponibles (BaseSelect)
 *
 * Esta es la prueba definitiva de nuestra arquitectura modular.
 */
const router = useRouter();
const route = useRoute();

// Composable principal: Equipment
const {
    loading: equipmentLoading,
    error: equipmentError,
    validationErrors,
    fetchEquipmentById,
    createEquipment,
    updateEquipment,
    clearErrors
} = useEquipment();

// Composable secundario: Labs (para el BaseSelect)
const {
    labs,
    loading: labsLoading,
    fetchLabs
} = useLabs();

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ESTADO DEL FORMULARIO
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Datos del formulario
const form = ref({
    identifier: '',
    type: '',
    lab_id: '',
    specifications: '',
    is_operational: '1' // Por defecto: operacional
});

// Estado de envío
const isSubmitting = ref(false);

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * COMPUTED PROPERTIES - LÓGICA DERIVADA
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Determina si estamos en modo edición
 * Basado en la presencia del parámetro 'id' en la ruta
 */
const isEditing = computed(() => {
    return !!route.params.id;
});

/**
 * Estado de carga inicial
 * Combina la carga de equipment (en modo edición) con la carga de labs
 */
const isLoadingInitialData = computed(() => {
    // En modo edición: esperamos tanto equipment como labs
    if (isEditing.value) {
        return equipmentLoading.value || labsLoading.value;
    }
    // En modo creación: solo esperamos labs
    return labsLoading.value;
});

/**
 * Estado de carga de laboratorios
 */
const isLoadingLabs = computed(() => labsLoading.value);

/**
 * Opciones para el BaseSelect de laboratorios
 *
 * TRANSFORMACIÓN CRÍTICA:
 * Convierte el array de labs del composable al formato que BaseSelect espera:
 * De: [{ id: 1, name: 'Lab A', ... }]
 * A:  [{ value: '1', text: 'Lab A' }]
 */
const labOptions = computed(() => {
    if (!labs.value || labs.value.length === 0) {
        return [];
    }

    return labs.value.map(lab => ({
        value: String(lab.id),
        text: `${lab.name}${lab.location ? ` - ${lab.location}` : ''}`
    }));
});

/**
 * Opciones para el estado operacional
 * Valores como strings para compatibilidad con la API Laravel
 */
const operationalStatusOptions = [
    { value: '1', text: '✅ Operacional - Equipo funcionando correctamente' },
    { value: '0', text: '❌ Fuera de Servicio - Equipo requiere mantenimiento o reparación' }
];

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * HELPERS - FUNCIONES AUXILIARES
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Obtiene el mensaje de error de validación para un campo específico
 *
 * @param {String} fieldName - Nombre del campo
 * @returns {String} Mensaje de error o cadena vacía
 */
const getFieldError = (fieldName) => {
    if (!validationErrors.value || !validationErrors.value[fieldName]) {
        return '';
    }

    // Laravel devuelve arrays de mensajes, tomamos el primero
    return validationErrors.value[fieldName][0] || '';
};

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CICLO DE VIDA - INICIALIZACIÓN
 * ═══════════════════════════════════════════════════════════════════════════
 */

onMounted(async () => {
    console.log('🚀 EquipmentCreateEditView montado');
    console.log('📝 Modo:', isEditing.value ? 'EDICIÓN' : 'CREACIÓN');

    try {
        // PASO 1: Cargar laboratorios (SIEMPRE necesario para el BaseSelect)
        console.log('📦 Paso 1: Cargando laboratorios...');
        await fetchLabs();
        console.log(`✅ Laboratorios cargados: ${labs.value.length}`);

        // PASO 2: Si estamos en modo edición, cargar datos del equipment
        if (isEditing.value) {
            const equipmentId = route.params.id;
            console.log(`📦 Paso 2: Cargando equipment ID ${equipmentId}...`);

            const equipment = await fetchEquipmentById(equipmentId);

            if (equipment) {
                // Poblar el formulario con los datos obtenidos
                form.value = {
                    identifier: equipment.identifier || '',
                    type: equipment.type || '',
                    lab_id: String(equipment.lab_id || ''),
                    specifications: equipment.specifications || '',
                    is_operational: String(equipment.is_operational ? '1' : '0')
                };

                console.log('✅ Formulario poblado con datos del equipment');
                console.log('📋 Datos:', form.value);
            } else {
                console.error('❌ No se pudo cargar el equipment');
            }
        } else {
            console.log('✅ Modo creación: Formulario con valores por defecto');
        }
    } catch (error) {
        console.error('❌ Error en la inicialización:', error);
    }
});

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MANEJO DEL FORMULARIO
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Maneja el envío del formulario
 *
 * FLUJO:
 * 1. Prevenir envío duplicado
 * 2. Preparar datos (convertir is_operational a número)
 * 3. Llamar a createEquipment o updateEquipment según el modo
 * 4. En caso de éxito: redirigir a la lista con mensaje de éxito
 * 5. En caso de error: mostrar errores de validación
 */
const handleSubmit = async () => {
    console.log('📤 Enviando formulario...');
    console.log('📋 Datos del formulario:', form.value);

    // Prevenir envíos múltiples
    if (isSubmitting.value) {
        console.log('⚠️ Ya hay un envío en progreso');
        return;
    }

    isSubmitting.value = true;
    clearErrors();

    try {
        // Preparar datos para enviar
        // Convertir is_operational de string a número para la API
        const dataToSend = {
            ...form.value,
            lab_id: Number(form.value.lab_id),
            is_operational: Number(form.value.is_operational)
        };

        console.log('📦 Datos a enviar:', dataToSend);

        let result;
        let successMessage;

        if (isEditing.value) {
            // MODO EDICIÓN
            console.log(`🔄 Actualizando equipment ID ${route.params.id}...`);
            result = await updateEquipment(route.params.id, dataToSend);
            successMessage = 'updated';
        } else {
            // MODO CREACIÓN
            console.log('🔄 Creando nuevo equipment...');
            result = await createEquipment(dataToSend);
            successMessage = 'created';
        }

        // Si llegamos aquí, la operación fue exitosa
        console.log('✅ Operación exitosa:', result);

        // Redirigir a la lista con mensaje de éxito
        router.push({
            name: 'equipment.index',
            query: { success: successMessage }
        });
    } catch (error) {
        // Los errores ya fueron manejados por el composable
        // Solo logueamos para debugging
        console.error('❌ Error en handleSubmit:', error);

        // El banner de error ya se mostrará automáticamente
        // gracias al binding de equipmentError en el template
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<style scoped>
/**
 * Estilos personalizados para transiciones suaves
 */
textarea {
    resize: vertical;
    min-height: 100px;
}

/* Animación para el spinner del botón */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
