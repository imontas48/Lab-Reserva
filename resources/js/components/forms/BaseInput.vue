<template>
    <!-- ═══════════════════════════════════════════════════════════════════════════
         COMPONENTE: BaseInput
         ═══════════════════════════════════════════════════════════════════════════

         PROPÓSITO:
         Componente base de input reutilizable para todos los formularios del sistema.
         Proporciona validación visual, accesibilidad y compatibilidad con v-model.

         CARACTERÍSTICAS:
         -  v-model compatible
         -  Accesibilidad (A11y) garantizada
         -  Estados visuales (normal, focus, error, disabled)
         -  Validación visual integrada
         -  Tailwind CSS con soporte dark mode

         USO:
         <BaseInput
             v-model="form.email"
             label="Correo Electrónico"
             type="email"
             placeholder="usuario@ejemplo.com"
             :error="errors.email"
             required
         />

         ═══════════════════════════════════════════════════════════════════════════ -->

    <div :class="containerClasses">
        <!-- ═══════════════════════════════════════════════════════════════════
             LABEL - Etiqueta del Input
             ═══════════════════════════════════════════════════════════════════

             ACCESIBILIDAD:
             - Asociada al input mediante 'for' e 'id'
             - Indicador visual de campo requerido
             ═══════════════════════════════════════════════════════════════════ -->
        <label
            v-if="label"
            :for="inputId"
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
             INPUT - Campo de Entrada
             ═══════════════════════════════════════════════════════════════════

             CARACTERÍSTICAS:
             - Compatible con v-model (modelValue + update:modelValue)
             - Clases dinámicas según estado (normal/error/disabled)
             - Atributos accesibles (id, aria-invalid, aria-describedby)
             - Soporte para todos los tipos HTML5
             ═══════════════════════════════════════════════════════════════════ -->
        <input
            :id="inputId"
            :name="name || undefined"
            :autocomplete="autocomplete || undefined"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :class="inputClasses"
            :aria-invalid="hasError"
            :aria-describedby="hasError ? errorId : undefined"
            @input="handleInput"
            @blur="handleBlur"
            @focus="handleFocus"
        />

        <!-- ═══════════════════════════════════════════════════════════════════
             ERROR MESSAGE - Mensaje de Error de Validación
             ═══════════════════════════════════════════════════════════════════

             COMPORTAMIENTO:
             - Solo se muestra si la prop 'error' tiene valor
             - Asociado al input mediante aria-describedby
             - Animación de entrada suave
             ═══════════════════════════════════════════════════════════════════ -->
        <transition name="error-fade">
            <p
                v-if="hasError"
                :id="errorId"
                :class="errorClasses"
                role="alert"
            >
                <!-- Icono de Error -->
                <svg
                    class="w-4 h-4 mr-1.5 flex-shrink-0"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    aria-hidden="true"
                >
                    <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                    />
                </svg>

                <!-- Mensaje de Error -->
                <span>{{ error }}</span>
            </p>
        </transition>
    </div>
</template>

<script setup>
// ═════════════════════════════════════════════════════════════════════════════
// IMPORTS
// ═════════════════════════════════════════════════════════════════════════════

import { computed, ref } from 'vue';

// ═════════════════════════════════════════════════════════════════════════════
// PROPS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Propiedades del componente BaseInput
 *
 * @property {String|Number} modelValue - Valor del input (v-model)
 * @property {String} label - Texto de la etiqueta
 * @property {String} type - Tipo de input HTML5
 * @property {String} placeholder - Texto de marcador de posición
 * @property {String} error - Mensaje de error de validación
 * @property {Boolean} required - Indica si el campo es obligatorio
 * @property {Boolean} disabled - Indica si el input está deshabilitado
 */
const props = defineProps({
    /** Atributo name del input, necesario para los gestores de contrasenas */
    name: {
        type: String,
        default: '',
    },

    /** Pista de autocompletado del navegador */
    autocomplete: {
        type: String,
        default: '',
    },

    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'text',
        validator: (value) => {
            // Validar que el tipo sea uno de los tipos HTML5 válidos
            const validTypes = [
                'text', 'email', 'password', 'number',
                'tel', 'url', 'search', 'date', 'time',
                'datetime-local', 'month', 'week'
            ];
            return validTypes.includes(value);
        }
    },
    placeholder: {
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

// ═════════════════════════════════════════════════════════════════════════════
// EMITS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Eventos emitidos por el componente
 *
 * @event update:modelValue - Emitido cuando el valor del input cambia
 * @event blur - Emitido cuando el input pierde el foco
 * @event focus - Emitido cuando el input recibe el foco
 */
const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

// ═════════════════════════════════════════════════════════════════════════════
// ESTADO REACTIVO
// ═════════════════════════════════════════════════════════════════════════════

/**
 * ID único para el input
 * Garantiza la accesibilidad correcta de la asociación label-input
 */
const inputId = computed(() => {
    // Generar ID único basado en timestamp y random
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(2, 9);
    return `base-input-${timestamp}-${random}`;
});

/**
 * ID único para el mensaje de error
 * Usado en aria-describedby para accesibilidad
 */
const errorId = computed(() => `${inputId.value}-error`);

/**
 * Indica si el input tiene un error
 */
const hasError = computed(() => !!props.error);

/**
 * Estado de foco del input
 * Se usa para estilos adicionales si es necesario
 */
const isFocused = ref(false);

// ═════════════════════════════════════════════════════════════════════════════
// COMPUTED PROPERTIES - CLASES CSS DINÁMICAS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Clases para el contenedor principal
 * ─────────────────────────────────────────────────────────────────────────────
 */
const containerClasses = computed(() => 'w-full');

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Clases para la etiqueta (label)
 * ─────────────────────────────────────────────────────────────────────────────
 */
const labelClasses = computed(() => {
    const baseClasses = 'block text-sm font-medium mb-1.5';

    // Clases según estado
    const stateClasses = props.disabled
        ? 'text-gray-400 dark:text-gray-500 cursor-not-allowed'
        : 'text-gray-700 dark:text-gray-300';

    return `${baseClasses} ${stateClasses}`;
});

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Clases para el input
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * ESTADOS MANEJADOS:
 * 1. Normal: Borde gris, fondo blanco
 * 2. Focus: Borde azul, anillo de enfoque
 * 3. Error: Borde rojo, texto rojo
 * 4. Disabled: Fondo gris, cursor no permitido
 * ─────────────────────────────────────────────────────────────────────────────
 */
const inputClasses = computed(() => {
    // Clases base (aplicadas siempre)
    const baseClasses = [
        'block w-full',
        'px-3 py-2',
        'text-sm',
        'rounded-lg',
        'transition-colors duration-200',
        'placeholder:text-gray-400 dark:placeholder:text-gray-500'
    ];

    // Clases según estado: Disabled
    if (props.disabled) {
        return [
            ...baseClasses,
            'bg-gray-100 dark:bg-gray-800',
            'text-gray-500 dark:text-gray-400',
            'border border-gray-300 dark:border-gray-600',
            'cursor-not-allowed',
            'opacity-60'
        ].join(' ');
    }

    // Clases según estado: Error
    if (hasError.value) {
        return [
            ...baseClasses,
            'bg-white dark:bg-gray-900',
            'text-gray-900 dark:text-white',
            'border-2 border-red-500 dark:border-red-400',
            'focus:outline-none focus:ring-2 focus:ring-red-500/50 dark:focus:ring-red-400/50',
            'focus:border-red-500 dark:focus:border-red-400'
        ].join(' ');
    }

    // Clases según estado: Normal/Focus
    return [
        ...baseClasses,
        'bg-white dark:bg-gray-900',
        'text-gray-900 dark:text-white',
        'border border-gray-300 dark:border-gray-600',
        'focus:outline-none focus:ring-2 focus:ring-indigo-500/50 dark:focus:ring-indigo-400/50',
        'focus:border-indigo-500 dark:focus:border-indigo-400',
        'hover:border-gray-400 dark:hover:border-gray-500'
    ].join(' ');
});

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Clases para el mensaje de error
 * ─────────────────────────────────────────────────────────────────────────────
 */
const errorClasses = computed(() => {
    return [
        'flex items-start',
        'mt-1.5',
        'text-sm',
        'text-red-600 dark:text-red-400'
    ].join(' ');
});

// ═════════════════════════════════════════════════════════════════════════════
// MÉTODOS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador del evento input
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Emite el evento update:modelValue para compatibilidad con v-model.
 * Maneja automáticamente la conversión de tipo para inputs numéricos.
 *
 * @param {Event} event - Evento nativo del input
 */
const handleInput = (event) => {
    let value = event.target.value;

    // Conversión automática para inputs de tipo número
    if (props.type === 'number' && value !== '') {
        value = parseFloat(value);

        // Si no es un número válido, mantener como string vacío
        if (isNaN(value)) {
            value = '';
        }
    }

    // Emitir evento para v-model
    emit('update:modelValue', value);
};

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador del evento blur
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Se ejecuta cuando el input pierde el foco.
 * Útil para validaciones que se disparan al salir del campo.
 *
 * @param {Event} event - Evento nativo del input
 */
const handleBlur = (event) => {
    isFocused.value = false;
    emit('blur', event);
};

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Manejador del evento focus
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Se ejecuta cuando el input recibe el foco.
 *
 * @param {Event} event - Evento nativo del input
 */
const handleFocus = (event) => {
    isFocused.value = true;
    emit('focus', event);
};
</script>

<style scoped>
/**
 * ═════════════════════════════════════════════════════════════════════════════
 * ESTILOS ESPECÍFICOS DEL COMPONENTE
 * ═════════════════════════════════════════════════════════════════════════════
 *
 * La mayoría de los estilos son manejados por Tailwind CSS.
 * Solo se incluyen estilos personalizados para animaciones.
 */

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Animación para mensaje de error
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Transición suave de entrada/salida para el mensaje de error.
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
 * ─────────────────────────────────────────────────────────────────────────────
 * Estilos adicionales para mejorar la experiencia
 * ─────────────────────────────────────────────────────────────────────────────
 */

/* Eliminar spinner de input number en Chrome/Safari */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Eliminar spinner de input number en Firefox */
input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
}

/*
 * El autorrelleno se estiliza en resources/css/app.css, no aquí.
 *
 * Aquí había una regla propia cuya variante oscura dependía de
 * `@media (prefers-color-scheme: dark)`, es decir, de la preferencia del
 * SISTEMA. Pero el tema de la aplicación lo gobierna la clase `.dark`
 * (`darkMode: 'class'`), que el usuario puede fijar a claro desde el selector
 * de tema. Con el sistema en oscuro y la aplicación en claro, las dos
 * condiciones se contradecían: la media query pintaba el campo autorrellenado
 * de gris muy oscuro mientras el texto heredaba el `text-gray-900` del modo
 * claro. Campo oscuro con letra casi negra, ilegible.
 *
 * Además, al ser un estilo `scoped` quedaba fuera de las capas de Tailwind y
 * ganaba a cualquier regla global, así que no bastaba con añadir la correcta.
 */
</style>
