/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THEME STORE - Gestión del Tema (Claro/Oscuro)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Store de Pinia para gestionar el tema de la aplicación.
 * Soporta tres modos: 'light', 'dark', 'system'
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

/**
 * Clave para almacenar la preferencia en localStorage
 */
const THEME_STORAGE_KEY = 'lab-reserva-theme';

/**
 * Valores válidos para el tema
 * @type {Array<'light' | 'dark' | 'system'>}
 */
const VALID_THEMES = ['light', 'dark', 'system'];

export const useThemeStore = defineStore('theme', () => {
    // ═══════════════════════════════════════════════════════════════════════
    // ESTADO
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Tema seleccionado por el usuario
     * @type {import('vue').Ref<'light' | 'dark' | 'system'>}
     */
    const theme = ref('system');

    /**
     * Indica si el sistema prefiere modo oscuro
     */
    const systemPrefersDark = ref(false);

    // ═══════════════════════════════════════════════════════════════════════
    // COMPUTED
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Determina si el modo oscuro está activo
     * Considera tanto la elección del usuario como la preferencia del sistema
     */
    const isDark = computed(() => {
        if (theme.value === 'system') {
            return systemPrefersDark.value;
        }
        return theme.value === 'dark';
    });

    /**
     * Tema efectivo actual (para mostrar en UI)
     */
    const effectiveTheme = computed(() => {
        return isDark.value ? 'dark' : 'light';
    });

    /**
     * Icono correspondiente al tema actual
     */
    const themeIcon = computed(() => {
        switch (theme.value) {
            case 'light':
                return 'sun';
            case 'dark':
                return 'moon';
            case 'system':
                return 'computer';
            default:
                return 'sun';
        }
    });

    // ═══════════════════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Aplica el tema al documento HTML
     */
    const applyTheme = () => {
        const root = document.documentElement;

        if (isDark.value) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }

        // Actualizar meta theme-color para móviles
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', isDark.value ? '#1a1a2e' : '#ffffff');
        }
    };

    /**
     * Detecta la preferencia del sistema
     */
    // El listener se registraba dentro de detectSystemPreference, que se llama
    // desde initializeTheme: cada invocacion anadia otro listener sobre el mismo
    // matchMedia, sin quitarlo nunca. Se registra una sola vez.
    let systemListenerAttached = false;

    const detectSystemPreference = () => {
        if (typeof window === 'undefined' || !window.matchMedia) {
            return;
        }

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        systemPrefersDark.value = mediaQuery.matches;

        if (systemListenerAttached) {
            return;
        }

        // El watch sobre systemPrefersDark ya reaplica el tema, asi que aqui
        // solo se actualiza el valor.
        mediaQuery.addEventListener('change', (e) => {
            systemPrefersDark.value = e.matches;
        });

        systemListenerAttached = true;
    };

    /**
     * Carga el tema guardado en localStorage
     */
    const loadSavedTheme = () => {
        if (typeof window !== 'undefined') {
            const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
            if (savedTheme && VALID_THEMES.includes(savedTheme)) {
                theme.value = savedTheme;
            }
        }
    };

    /**
     * Guarda el tema en localStorage
     */
    const saveTheme = () => {
        if (typeof window !== 'undefined') {
            localStorage.setItem(THEME_STORAGE_KEY, theme.value);
        }
    };

    // ═══════════════════════════════════════════════════════════════════════
    // MÉTODOS PÚBLICOS (ACTIONS)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Establece un tema específico
     * @param {'light' | 'dark' | 'system'} newTheme
     */
    const setTheme = (newTheme) => {
        if (VALID_THEMES.includes(newTheme)) {
            theme.value = newTheme;
            saveTheme();
            applyTheme();
        } else {
            console.warn(`Tema inválido: ${newTheme}. Valores válidos: ${VALID_THEMES.join(', ')}`);
        }
    };

    /**
     * Alterna entre modo claro y oscuro
     * Si está en 'system', cambia al opuesto del actual
     */
    const toggleTheme = () => {
        if (isDark.value) {
            setTheme('light');
        } else {
            setTheme('dark');
        }
    };

    /**
     * Cicla entre los tres modos: light -> dark -> system -> light
     */
    const cycleTheme = () => {
        const currentIndex = VALID_THEMES.indexOf(theme.value);
        const nextIndex = (currentIndex + 1) % VALID_THEMES.length;
        setTheme(VALID_THEMES[nextIndex]);
    };

    /**
     * Inicializa el store del tema
     * Debe llamarse al montar la aplicación
     */
    const initializeTheme = () => {
        detectSystemPreference();
        loadSavedTheme();
        applyTheme();
    };

    // ═══════════════════════════════════════════════════════════════════════
    // WATCHERS
    // ═══════════════════════════════════════════════════════════════════════

    // Aplicar tema cuando cambie el valor del theme o systemPrefersDark
    watch([theme, systemPrefersDark], () => {
        applyTheme();
    });

    // ═══════════════════════════════════════════════════════════════════════
    // RETORNO
    // ═══════════════════════════════════════════════════════════════════════

    return {
        // Estado
        theme,
        systemPrefersDark,

        // Computed
        isDark,
        effectiveTheme,
        themeIcon,

        // Acciones
        setTheme,
        toggleTheme,
        cycleTheme,
        initializeTheme,
    };
});
