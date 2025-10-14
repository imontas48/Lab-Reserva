/**
 * ═══════════════════════════════════════════════════════════════════════════
 * COMPOSABLE: useToast
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Wrapper para vue-toastification que proporciona métodos consistentes
 * para mostrar notificaciones en toda la aplicación.
 *
 * USO:
 * ```js
 * import { useToast } from '@/composables/useToast';
 *
 * const toast = useToast();
 * toast.success('Operación exitosa');
 * toast.error('Algo salió mal');
 * toast.warning('Ten cuidado');
 * toast.info('Información importante');
 * ```
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { useToast as useToastification } from 'vue-toastification';

export function useToast() {
    const toast = useToastification();

    return {
        /**
         * Mostrar notificación de éxito
         * @param {String} message - Mensaje a mostrar
         * @param {Object} options - Opciones adicionales
         */
        success(message, options = {}) {
            toast.success(message, {
                timeout: 3000,
                ...options
            });
        },

        /**
         * Mostrar notificación de error
         * @param {String} message - Mensaje a mostrar
         * @param {Object} options - Opciones adicionales
         */
        error(message, options = {}) {
            toast.error(message, {
                timeout: 5000, // Los errores duran más tiempo
                ...options
            });
        },

        /**
         * Mostrar notificación de advertencia
         * @param {String} message - Mensaje a mostrar
         * @param {Object} options - Opciones adicionales
         */
        warning(message, options = {}) {
            toast.warning(message, {
                timeout: 4000,
                ...options
            });
        },

        /**
         * Mostrar notificación informativa
         * @param {String} message - Mensaje a mostrar
         * @param {Object} options - Opciones adicionales
         */
        info(message, options = {}) {
            toast.info(message, {
                timeout: 3000,
                ...options
            });
        },

        /**
         * Limpiar todas las notificaciones
         */
        clear() {
            toast.clear();
        },

        /**
         * Acceso directo al toast original para casos avanzados
         */
        toast
    };
}
