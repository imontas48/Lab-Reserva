/**
 * ═══════════════════════════════════════════════════════════════════════════
 * LAB-RESERVA FRONTEND APPLICATION
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Aplicación Vue 3 con:
 * - Vue Router para navegación
 * - Pinia para gestión de estado
 * - Axios para comunicación con API
 * - Tailwind CSS para estilos
 * - Vue Toastification para notificaciones
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

import './bootstrap';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import { RouterView } from 'vue-router';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

/**
 * Crear instancia de Pinia (gestión de estado)
 */
const pinia = createPinia();

/**
 * Configuración de Toast
 */
const toastOptions = {
    position: 'top-right',
    timeout: 3000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: false,
    transition: 'Vue-Toastification__bounce',
    maxToasts: 5,
    newestOnTop: true
};

/**
 * Crear aplicación Vue
 *
 * Usando render function en lugar de template para evitar
 * necesitar el compilador de Vue en runtime
 */
const app = createApp({
    render: () => h(RouterView)
});

/**
 * Usar plugins
 */
app.use(pinia);  // Gestión de estado
app.use(router); // Enrutamiento
app.use(Toast, toastOptions); // Sistema de notificaciones

/**
 * Montar la aplicación en el elemento #app
 */
app.mount('#app');

console.log('🚀 Lab-Reserva Frontend iniciado correctamente');
