<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         COMPONENTE: BaseSelect
         ═══════════════════════════════════════════════════════════════════════════

         PROPÓSITO:
         Componente base de select (dropdown) reutilizable para todos los formularios
         del sistema. Proporciona validación visual, accesibilidad y compatibilidad
         con v-model.

         CARACTERÍSTICAS:
         - ✅ v-model compatible
         - ✅ Accesibilidad (A11y) garantizada
         - ✅ Estados visuales (normal, focus, error, disabled)
         - ✅ Validación visual integrada
         - ✅ Soporte para relaciones (options con value/text)
         - ✅ Tailwind CSS con soporte dark mode

         USO:
         <BaseSelect
             v-model="form.lab_id"
             label="Laboratorio"
             :options="labOptions"
             placeholder="Seleccione un laboratorio..."
             :error="errors.lab_id"
             required
         />

         ═══════════════════════════════════════════════════════════════════════════ -->

    <div :class="containerClasses">
        <!-- ═══════════════════════════════════════════════════════════════════
             LABEL - Etiqueta del Select
             ═══════════════════════════════════════════════════════════════════

             ACCESIBILIDAD:
             - Asociada al select mediante 'for' e 'id'
             - Indicador visual de campo requerido
             ═══════════════════════════════════════════════════════════════════ -->
        <label
            v-if="label"
            :for="selectId"
            :class="labelClasses"
        >
            {{ label }}

            <!-- Indicador de Campo Requerido -->
            <span
                v-if="required"
                class="text-red-500 dark:text-red-400 ml-1"
                aria-label="Campo requerido"
            >
                *
            </span>
        </label>

        <!-- ═══════════════════════════════════════════════════════════════════
             SELECT - Campo de Selección
             ═══════════════════════════════════════════════════════════════════

             CARACTERÍSTICAS:
             - Compatible con v-model (modelValue + update:modelValue)
             - Clases dinámicas según estado (normal/error/disabled)
             - Atributos accesibles (id, aria-invalid, aria-describedby)
             - Primera opción como placeholder
             ═══════════════════════════════════════════════════════════════════ -->
        <select
            :id="selectId"
            :value="modelValue"
            :disabled="disabled"
            :required="required"
            :class="selectClasses"
            :aria-invalid="hasError"
            :aria-describedby="hasError ? errorId : undefined"
            @change="handleChange"
            @blur="handleBlur"
            @focus="handleFocus"
        >
            <!-- Opción Placeholder (Deshabilitada) -->
            <option
                value=""
                disabled
                selected
            >
                {{ placeholder }}
            </option>

            <!-- Opciones Dinámicas -->
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.text }}
            </option>
        </select>

        <!-- ═══════════════════════════════════════════════════════════════════
             MENSAJE DE ERROR
             ═══════════════════════════════════════════════════════════════════

             CARACTERÍSTICAS:
             - Solo visible cuando hay error
             - Animación suave de entrada/salida (error-fade)
             - Asociado al select mediante aria-describedby
             - Rol "alert" para accesibilidad
             ═══════════════════════════════════════════════════════════════════ -->
        <transition name="error-fade">
            <p
                v-if="hasError"
                :id="errorId"
                :class="errorClasses"
                role="alert"
            >
                {{ error }}
            </p>
        </transition>
    </div>
</template>

<script setup>
import { computed } from 'vue';

// ═════════════════════════════════════════════════════════════════════════════
// PROPS
// ═════════════════════════════════════════════════════════════════════════════

const props = defineProps({
    /**
     * Valor del select (v-model)
     * Puede ser String, Number o null
     *
     * @type {String|Number|null}
     * @default ''
     */
    modelValue: {
        type: [String, Number],
        default: ''
    },

    /**
     * Texto de la etiqueta del campo
     *
     * @type {String}
     * @default ''
     */
    label: {
        type: String,
        default: ''
    },

    /**
     * Array de opciones para el select
     * Cada opción debe tener la estructura: { value: any, text: String }
     *
     * Ejemplo:
     * [
     *   { value: 1, text: 'Laboratorio A' },
     *   { value: 2, text: 'Laboratorio B' }
     * ]
     *
     * @type {Array}
     * @required
     */
    options: {
        type: Array,
        required: true,
        default: () => []
    },

    /**
     * Texto del placeholder (primera opción deshabilitada)
     *
     * @type {String}
     * @default 'Seleccione una opción...'
     */
    placeholder: {
        type: String,
        default: 'Seleccione una opción...'
    },

    /**
     * Mensaje de error de validación
     * Si está presente, el campo se mostrará en estado de error
     *
     * @type {String}
     * @default ''
     */
    error: {
        type: String,
        default: ''
    },

    /**
     * Indica si el campo es requerido
     * Muestra un asterisco (*) en la etiqueta
     *
     * @type {Boolean}
     * @default false
     */
    required: {
        type: Boolean,
        default: false
    },

    /**
     * Indica si el campo está deshabilitado
     *
     * @type {Boolean}
     * @default false
     */
    disabled: {
        type: Boolean,
        default: false
    }
});

// ═════════════════════════════════════════════════════════════════════════════
// EMITS
// ═════════════════════════════════════════════════════════════════════════════

const emit = defineEmits([
    /**
     * Emitido cuando cambia el valor (v-model)
     * @param {String|Number} value - Nuevo valor seleccionado
     */
    'update:modelValue',

    /**
     * Emitido cuando el select pierde el foco
     * @param {Event} event - Evento blur
     */
    'blur',

    /**
     * Emitido cuando el select recibe el foco
     * @param {Event} event - Evento focus
     */
    'focus'
]);

// ═════════════════════════════════════════════════════════════════════════════
// COMPUTED PROPERTIES
// ═════════════════════════════════════════════════════════════════════════════

/**
 * ID único para el select
 * Garantiza la asociación correcta entre label y select
 * Usa un número aleatorio para evitar colisiones en la misma página
 */
const selectId = computed(() => {
    return `base-select-${Math.random().toString(36).substr(2, 9)}`;
});

/**
 * ID único para el mensaje de error
 * Permite la asociación mediante aria-describedby
 */
const errorId = computed(() => {
    return `${selectId.value}-error`;
});

/**
 * Indica si el campo tiene un error
 */
const hasError = computed(() => {
    return !!props.error;
});

/**
 * Clases del contenedor principal
 * Agrupa label, select y mensaje de error
 */
const containerClasses = computed(() => {
    return 'w-full';
});

/**
 * Clases de la etiqueta (label)
 */
const labelClasses = computed(() => {
    return [
        'block',
        'text-sm',
        'font-medium',
        'mb-2',
        // Colores
        'text-gray-700',
        'dark:text-gray-300'
    ].join(' ');
});

/**
 * Clases del select
 * Cambian según el estado (normal, error, disabled)
 */
const selectClasses = computed(() => {
    const baseClasses = [
        // Layout y sizing
        'w-full',
        'px-4',
        'py-2',
        'rounded-lg',

        // Typography
        'text-base',

        // Transiciones
        'transition-colors',
        'duration-200',

        // Focus
        'focus:outline-none',
        'focus:ring-2',
        'focus:ring-offset-0',

        // Apariencia del select
        'appearance-none',
        'bg-white',
        'dark:bg-gray-700',

        // Icono de dropdown (usando background)
        'bg-no-repeat',
        'bg-right',
        'pr-10',

        // Texto
        'text-gray-900',
        'dark:text-white'
    ];

    // Estado: Error
    if (hasError.value) {
        return [
            ...baseClasses,
            'border-2',
            'border-red-500',
            'focus:border-red-500',
            'focus:ring-red-500'
        ].join(' ');
    }

    // Estado: Disabled
    if (props.disabled) {
        return [
            ...baseClasses,
            'border',
            'border-gray-300',
            'dark:border-gray-600',
            'bg-gray-100',
            'dark:bg-gray-800',
            'cursor-not-allowed',
            'opacity-60'
        ].join(' ');
    }

    // Estado: Normal
    return [
        ...baseClasses,
        'border',
        'border-gray-300',
        'dark:border-gray-600',
        'hover:border-gray-400',
        'dark:hover:border-gray-500',
        'focus:border-indigo-500',
        'focus:ring-indigo-500'
    ].join(' ');
});

/**
 * Clases del mensaje de error
 */
const errorClasses = computed(() => {
    return [
        'mt-2',
        'text-sm',
        'text-red-600',
        'dark:text-red-400'
    ].join(' ');
});

// ═════════════════════════════════════════════════════════════════════════════
// MÉTODOS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Maneja el evento change del select
 * Emite el nuevo valor para v-model
 *
 * @param {Event} event - Evento change nativo
 */
const handleChange = (event) => {
    const value = event.target.value;

    // Emitir el valor como está (string)
    // El componente padre puede convertirlo si es necesario
    emit('update:modelValue', value);
};

/**
 * Maneja el evento blur (pérdida de foco)
 *
 * @param {Event} event - Evento blur nativo
 */
const handleBlur = (event) => {
    emit('blur', event);
};

/**
 * Maneja el evento focus (recepción de foco)
 *
 * @param {Event} event - Evento focus nativo
 */
const handleFocus = (event) => {
    emit('focus', event);
};
</script>

<style scoped>
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ESTILOS PERSONALIZADOS
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Icono de dropdown personalizado
 * Usa un SVG codificado como data URI para el icono de flecha
 */
select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-size: 1.5em 1.5em;
    background-position: right 0.5rem center;
}

/**
 * Icono de dropdown para dark mode
 */
.dark select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%9CA3AF'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
}

/**
 * Animación de error (fade in/out)
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

/**
 * Estilos de autofill (cuando el navegador autocompleta)
 */
select:-webkit-autofill,
select:-webkit-autofill:hover,
select:-webkit-autofill:focus {
    -webkit-text-fill-color: inherit;
    -webkit-box-shadow: 0 0 0 1000px white inset;
    transition: background-color 5000s ease-in-out 0s;
}

.dark select:-webkit-autofill,
.dark select:-webkit-autofill:hover,
.dark select:-webkit-autofill:focus {
    -webkit-text-fill-color: white;
    -webkit-box-shadow: 0 0 0 1000px #374151 inset;
}

/**
 * Remover estilos nativos del select en diferentes navegadores
 */
select::-ms-expand {
    display: none; /* IE */
}
</style>
