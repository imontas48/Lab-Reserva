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
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';

/**
 * Crear instancia de Pinia (gestión de estado)
 */
const pinia = createPinia();

/**
 * Crear aplicación Vue
 *
 * Nota: No necesitamos importar un componente raíz específico
 * porque usamos <router-view> directamente en app.blade.php
 */
const app = createApp({
    /**
     * Componente raíz vacío
     * El contenido se renderiza mediante <router-view>
     */
    template: '<router-view />',
});

/**
 * Usar plugins
 */
app.use(pinia);  // Gestión de estado
app.use(router); // Enrutamiento

/**
 * Montar la aplicación en el elemento #app
 */
app.mount('#app');

console.log('🚀 Lab-Reserva Frontend iniciado correctamente');
