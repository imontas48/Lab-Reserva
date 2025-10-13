import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

/**
 * Store de ejemplo para demostrar Pinia
 * 
 * Este store demuestra:
 * - Uso de Composition API con Pinia
 * - State reactivo con ref()
 * - Getters con computed()
 * - Actions para mutaciones
 */
export const useExampleStore = defineStore('example', () => {
    // State
    const counter = ref(0);
    const message = ref('Bienvenido a Lab-Reserva');

    // Getters
    const doubleCounter = computed(() => counter.value * 2);
    const greeting = computed(() => `${message.value} - Contador: ${counter.value}`);

    // Actions
    function increment() {
        counter.value++;
    }

    function decrement() {
        counter.value--;
    }

    function reset() {
        counter.value = 0;
    }

    function updateMessage(newMessage) {
        message.value = newMessage;
    }

    return {
        // State
        counter,
        message,
        // Getters
        doubleCounter,
        greeting,
        // Actions
        increment,
        decrement,
        reset,
        updateMessage,
    };
});
