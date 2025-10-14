<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         VISTA DE PRUEBA: BaseSelect Component
         ═══════════════════════════════════════════════════════════════════════════

         Esta vista demuestra todos los estados y casos de uso del componente BaseSelect.
         Útil para desarrollo, testing y documentación visual.

         ═══════════════════════════════════════════════════════════════════════════ -->

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    BaseSelect Component - Test & Demo
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Demostración completa de todos los estados y funcionalidades del componente BaseSelect
                </p>
            </div>

            <!-- Grid de ejemplos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 1: Select Básico
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        1. Select Básico
                    </h2>

                    <BaseSelect
                        v-model="basicSelect"
                        label="Laboratorio"
                        :options="labOptions"
                        placeholder="Seleccione un laboratorio..."
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Valor: <code class="text-indigo-600 dark:text-indigo-400">{{ basicSelect || '(no seleccionado)' }}</code>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Tipo: {{ typeof basicSelect }}
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 2: Select con Error
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        2. Select con Error de Validación
                    </h2>

                    <BaseSelect
                        v-model="errorSelect"
                        label="Tipo de Equipo"
                        :options="equipmentTypeOptions"
                        placeholder="Seleccione el tipo..."
                        :error="errorSelectMessage"
                        required
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Valor: <code class="text-indigo-600 dark:text-indigo-400">{{ errorSelect || '(no seleccionado)' }}</code>
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 3: Select Deshabilitado
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        3. Select Deshabilitado
                    </h2>

                    <BaseSelect
                        v-model="disabledSelect"
                        label="Estado del Sistema"
                        :options="systemStatusOptions"
                        disabled
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Este campo está deshabilitado y no se puede modificar
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 4: Select con Valores Numéricos
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        4. Select con IDs Numéricos
                    </h2>

                    <BaseSelect
                        v-model.number="numericSelect"
                        label="Software Instalado"
                        :options="softwareOptions"
                        placeholder="Seleccione el software..."
                        required
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Valor: <code class="text-indigo-600 dark:text-indigo-400">{{ numericSelect || '(no seleccionado)' }}</code>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Tipo: {{ typeof numericSelect }}
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 5: Select con Placeholder Personalizado
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        5. Placeholder Personalizado
                    </h2>

                    <BaseSelect
                        v-model="placeholderSelect"
                        label="Prioridad"
                        :options="priorityOptions"
                        placeholder="-- Elija la prioridad --"
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Valor: <code class="text-indigo-600 dark:text-indigo-400">{{ placeholderSelect || '(no seleccionado)' }}</code>
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 6: Select con Muchas Opciones
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        6. Select con Lista Larga
                    </h2>

                    <BaseSelect
                        v-model="longListSelect"
                        label="Día del Mes"
                        :options="dayOptions"
                        placeholder="Seleccione un día..."
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Día seleccionado: <code class="text-indigo-600 dark:text-indigo-400">{{ longListSelect || '(ninguno)' }}</code>
                        </p>
                    </div>
                </div>

            </div>

            <!-- Sección de formulario completo -->
            <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                    7. Formulario Completo con Validación
                </h2>

                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Select: Laboratorio -->
                        <BaseSelect
                            v-model="form.lab_id"
                            label="Laboratorio"
                            :options="labOptions"
                            placeholder="Seleccione un laboratorio..."
                            :error="formErrors.lab_id"
                            required
                        />

                        <!-- Select: Tipo de Equipo -->
                        <BaseSelect
                            v-model="form.equipment_type"
                            label="Tipo de Equipo"
                            :options="equipmentTypeOptions"
                            placeholder="Seleccione el tipo..."
                            :error="formErrors.equipment_type"
                            required
                        />
                    </div>

                    <!-- Select: Software -->
                    <BaseSelect
                        v-model.number="form.software_id"
                        label="Software Instalado"
                        :options="softwareOptions"
                        placeholder="Seleccione el software..."
                        :error="formErrors.software_id"
                    />

                    <!-- Select: Estado Operacional -->
                    <BaseSelect
                        v-model="form.is_operational"
                        label="Estado Operacional"
                        :options="operationalOptions"
                        placeholder="Seleccione el estado..."
                        :error="formErrors.is_operational"
                        required
                    />

                    <div class="flex gap-4">
                        <button
                            type="submit"
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors"
                        >
                            Validar Formulario
                        </button>

                        <button
                            type="button"
                            @click="resetForm"
                            class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors"
                        >
                            Resetear
                        </button>
                    </div>

                    <!-- Resultado de validación -->
                    <div
                        v-if="formSubmitted"
                        class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"
                    >
                        <p class="text-green-800 dark:text-green-400 font-medium">
                            ✅ Formulario válido! Los datos se procesarían aquí.
                        </p>
                        <pre class="mt-2 text-sm text-green-700 dark:text-green-300">{{ JSON.stringify(form, null, 2) }}</pre>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import BaseSelect from '@/components/forms/BaseSelect.vue';

// ═════════════════════════════════════════════════════════════════════════════
// OPCIONES DE DATOS
// ═════════════════════════════════════════════════════════════════════════════

const labOptions = [
    { value: '1', text: 'Laboratorio A - Edificio Norte' },
    { value: '2', text: 'Laboratorio B - Edificio Sur' },
    { value: '3', text: 'Laboratorio C - Edificio Este' },
    { value: '4', text: 'Laboratorio D - Edificio Oeste' }
];

const equipmentTypeOptions = [
    { value: 'desktop', text: 'Computadora de Escritorio' },
    { value: 'laptop', text: 'Laptop' },
    { value: 'server', text: 'Servidor' },
    { value: 'printer', text: 'Impresora' },
    { value: 'scanner', text: 'Escáner' }
];

const softwareOptions = [
    { value: 1, text: 'Adobe Photoshop CC 2024' },
    { value: 2, text: 'Microsoft Office 365' },
    { value: 3, text: 'AutoCAD 2024' },
    { value: 4, text: 'Visual Studio Code' },
    { value: 5, text: 'IntelliJ IDEA' }
];

const systemStatusOptions = [
    { value: 'active', text: 'Activo' },
    { value: 'inactive', text: 'Inactivo' },
    { value: 'maintenance', text: 'En Mantenimiento' }
];

const priorityOptions = [
    { value: 'low', text: 'Baja' },
    { value: 'medium', text: 'Media' },
    { value: 'high', text: 'Alta' },
    { value: 'critical', text: 'Crítica' }
];

const operationalOptions = [
    { value: '1', text: 'Operacional' },
    { value: '0', text: 'Fuera de Servicio' }
];

// Generar opciones de días del mes (1-31)
const dayOptions = Array.from({ length: 31 }, (_, i) => ({
    value: String(i + 1),
    text: `Día ${i + 1}`
}));

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 1: Select Básico
// ═════════════════════════════════════════════════════════════════════════════

const basicSelect = ref('');

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 2: Select con Error
// ═════════════════════════════════════════════════════════════════════════════

const errorSelect = ref('');

const errorSelectMessage = computed(() => {
    if (!errorSelect.value) return 'El tipo de equipo es requerido';
    return '';
});

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 3: Select Deshabilitado
// ═════════════════════════════════════════════════════════════════════════════

const disabledSelect = ref('active');

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 4: Select con Valores Numéricos
// ═════════════════════════════════════════════════════════════════════════════

const numericSelect = ref(null);

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 5: Placeholder Personalizado
// ═════════════════════════════════════════════════════════════════════════════

const placeholderSelect = ref('');

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 6: Lista Larga
// ═════════════════════════════════════════════════════════════════════════════

const longListSelect = ref('');

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 7: Formulario Completo
// ═════════════════════════════════════════════════════════════════════════════

const form = ref({
    lab_id: '',
    equipment_type: '',
    software_id: null,
    is_operational: ''
});

const formSubmitted = ref(false);

// Errores de validación del formulario
const formErrors = computed(() => {
    const errors = {};

    // Validar laboratorio
    if (!form.value.lab_id) {
        errors.lab_id = 'El laboratorio es requerido';
    }

    // Validar tipo de equipo
    if (!form.value.equipment_type) {
        errors.equipment_type = 'El tipo de equipo es requerido';
    }

    // Validar estado operacional
    if (!form.value.is_operational) {
        errors.is_operational = 'El estado operacional es requerido';
    }

    // Software es opcional, no genera error si está vacío

    return errors;
});

// Verificar si el formulario es válido
const isFormValid = computed(() => {
    return Object.keys(formErrors.value).length === 0;
});

// Manejar envío del formulario
const handleSubmit = () => {
    if (isFormValid.value) {
        formSubmitted.value = true;
        console.log('Formulario válido:', form.value);

        // Ocultar mensaje después de 5 segundos
        setTimeout(() => {
            formSubmitted.value = false;
        }, 5000);
    } else {
        console.log('Formulario inválido:', formErrors.value);
    }
};

// Resetear formulario
const resetForm = () => {
    form.value = {
        lab_id: '',
        equipment_type: '',
        software_id: null,
        is_operational: ''
    };
    formSubmitted.value = false;
};
</script>
