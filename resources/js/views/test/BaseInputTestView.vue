<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         VISTA DE PRUEBA: BaseInput Component
         ═══════════════════════════════════════════════════════════════════════════

         Esta vista demuestra todos los estados y casos de uso del componente BaseInput.
         Útil para desarrollo, testing y documentación visual.

         ═══════════════════════════════════════════════════════════════════════════ -->

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    BaseInput Component - Test & Demo
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Demostración completa de todos los estados y funcionalidades del componente BaseInput
                </p>
            </div>

            <!-- Grid de ejemplos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 1: Input Básico
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        1. Input Básico
                    </h2>

                    <BaseInput
                        v-model="basicInput"
                        label="Nombre de Usuario"
                        placeholder="Ingresa tu nombre de usuario"
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Valor: <code class="text-indigo-600 dark:text-indigo-400">{{ basicInput || '(vacío)' }}</code>
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 2: Input con Error
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        2. Input con Error de Validación
                    </h2>

                    <BaseInput
                        v-model="emailInput"
                        label="Correo Electrónico"
                        type="email"
                        placeholder="usuario@ejemplo.com"
                        :error="emailError"
                        required
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Valor: <code class="text-indigo-600 dark:text-indigo-400">{{ emailInput || '(vacío)' }}</code>
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 3: Input Deshabilitado
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        3. Input Deshabilitado
                    </h2>

                    <BaseInput
                        v-model="disabledInput"
                        label="Código de Sistema"
                        disabled
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Este campo está deshabilitado y no se puede editar
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 4: Input de Contraseña
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        4. Input de Contraseña
                    </h2>

                    <BaseInput
                        v-model="passwordInput"
                        label="Contraseña"
                        type="password"
                        placeholder="Mínimo 8 caracteres"
                        :error="passwordError"
                        required
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Longitud: <code class="text-indigo-600 dark:text-indigo-400">{{ passwordInput.length }} caracteres</code>
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 5: Input Numérico
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        5. Input Numérico
                    </h2>

                    <BaseInput
                        v-model.number="numberInput"
                        label="Capacidad del Laboratorio"
                        type="number"
                        placeholder="Ej: 30"
                        :error="numberError"
                        required
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Valor: <code class="text-indigo-600 dark:text-indigo-400">{{ numberInput || 0 }}</code>
                            <span class="ml-2">(Tipo: {{ typeof numberInput }})</span>
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     EJEMPLO 6: Input de Fecha
                     ═══════════════════════════════════════════════════════════ -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        6. Input de Fecha
                    </h2>

                    <BaseInput
                        v-model="dateInput"
                        label="Fecha de Reserva"
                        type="date"
                        required
                    />

                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Valor: <code class="text-indigo-600 dark:text-indigo-400">{{ dateInput || '(no seleccionada)' }}</code>
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
                        <BaseInput
                            v-model="form.firstName"
                            label="Nombre"
                            placeholder="Ej: Juan"
                            :error="formErrors.firstName"
                            required
                        />

                        <BaseInput
                            v-model="form.lastName"
                            label="Apellido"
                            placeholder="Ej: Pérez"
                            :error="formErrors.lastName"
                            required
                        />
                    </div>

                    <BaseInput
                        v-model="form.email"
                        label="Correo Electrónico"
                        type="email"
                        placeholder="usuario@ejemplo.com"
                        :error="formErrors.email"
                        required
                    />

                    <BaseInput
                        v-model="form.phone"
                        label="Teléfono"
                        type="tel"
                        placeholder="+52 123 456 7890"
                        :error="formErrors.phone"
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
import BaseInput from '@/components/forms/BaseInput.vue';

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 1: Input Básico
// ═════════════════════════════════════════════════════════════════════════════

const basicInput = ref('');

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 2: Input con Error
// ═════════════════════════════════════════════════════════════════════════════

const emailInput = ref('');

const emailError = computed(() => {
    if (!emailInput.value) return 'El correo electrónico es requerido';
    if (!emailInput.value.includes('@')) return 'Formato de correo inválido';
    return '';
});

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 3: Input Deshabilitado
// ═════════════════════════════════════════════════════════════════════════════

const disabledInput = ref('SYS-2024-LAB-001');

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 4: Input de Contraseña
// ═════════════════════════════════════════════════════════════════════════════

const passwordInput = ref('');

const passwordError = computed(() => {
    if (!passwordInput.value) return 'La contraseña es requerida';
    if (passwordInput.value.length < 8) return 'La contraseña debe tener al menos 8 caracteres';
    return '';
});

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 5: Input Numérico
// ═════════════════════════════════════════════════════════════════════════════

const numberInput = ref(null);

const numberError = computed(() => {
    if (!numberInput.value) return 'La capacidad es requerida';
    if (numberInput.value < 1) return 'La capacidad debe ser mayor a 0';
    if (numberInput.value > 100) return 'La capacidad máxima es 100';
    return '';
});

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 6: Input de Fecha
// ═════════════════════════════════════════════════════════════════════════════

const dateInput = ref('');

// ═════════════════════════════════════════════════════════════════════════════
// EJEMPLO 7: Formulario Completo
// ═════════════════════════════════════════════════════════════════════════════

const form = ref({
    firstName: '',
    lastName: '',
    email: '',
    phone: ''
});

const formSubmitted = ref(false);

// Errores de validación del formulario
const formErrors = computed(() => {
    const errors = {};

    // Validar nombre
    if (!form.value.firstName) {
        errors.firstName = 'El nombre es requerido';
    }

    // Validar apellido
    if (!form.value.lastName) {
        errors.lastName = 'El apellido es requerido';
    }

    // Validar email
    if (!form.value.email) {
        errors.email = 'El email es requerido';
    } else if (!form.value.email.includes('@')) {
        errors.email = 'Formato de email inválido';
    }

    // Validar teléfono (opcional, pero si tiene valor debe ser válido)
    if (form.value.phone && form.value.phone.length < 10) {
        errors.phone = 'El teléfono debe tener al menos 10 dígitos';
    }

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
        firstName: '',
        lastName: '',
        email: '',
        phone: ''
    };
    formSubmitted.value = false;
};
</script>
