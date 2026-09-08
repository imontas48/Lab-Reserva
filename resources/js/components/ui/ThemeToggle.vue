<template>
  <div class="relative">
    <!-- Botón principal (toggle rápido) -->
    <button
      v-if="variant === 'toggle'"
      @click="themeStore.toggleTheme()"
      class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
    >
      <!-- Icono Sol (modo claro) -->
      <svg
        v-if="!isDark"
        class="h-5 w-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
        />
      </svg>

      <!-- Icono Luna (modo oscuro) -->
      <svg
        v-else
        class="h-5 w-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
        />
      </svg>
    </button>

    <!-- Dropdown con 3 opciones -->
    <div v-else-if="variant === 'dropdown'" class="relative">
      <button
        @click="isOpen = !isOpen"
        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-300 dark:hover:bg-gray-700"
      >
        <!-- Icono actual -->
        <component :is="currentIcon" class="h-5 w-5" />
        <span v-if="showLabel">{{ currentLabel }}</span>
        <!-- Chevron -->
        <svg
          class="h-4 w-4 transition-transform"
          :class="{ 'rotate-180': isOpen }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </button>

      <!-- Menú dropdown -->
      <Transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div
          v-if="isOpen"
          class="absolute right-0 z-50 mt-2 w-40 origin-top-right rounded-lg bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700"
          v-click-outside="() => (isOpen = false)"
        >
          <button
            v-for="option in themeOptions"
            :key="option.value"
            @click="selectTheme(option.value)"
            class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            :class="{
              'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400':
                themeStore.theme === option.value,
            }"
          >
            <component :is="option.icon" class="h-4 w-4" />
            {{ option.label }}
            <!-- Check si está seleccionado -->
            <svg
              v-if="themeStore.theme === option.value"
              class="ml-auto h-4 w-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>
      </Transition>
    </div>

    <!-- Switch con etiquetas -->
    <div
      v-else-if="variant === 'switch'"
      class="flex items-center gap-3"
    >
      <!-- Icono sol -->
      <SunIcon class="h-5 w-5 text-gray-400 dark:text-gray-500" />

      <!-- Switch -->
      <button
        @click="themeStore.toggleTheme()"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        :class="isDark ? 'bg-blue-600' : 'bg-gray-200'"
        role="switch"
        :aria-checked="isDark"
      >
        <span
          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
          :class="isDark ? 'translate-x-5' : 'translate-x-0'"
        />
      </button>

      <!-- Icono luna -->
      <MoonIcon class="h-5 w-5 text-gray-400 dark:text-gray-500" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { vClickOutside } from '@/directives/clickOutside';
import { useThemeStore } from '@/stores/theme';

// ═══════════════════════════════════════════════════════════════════════════
// PROPS
// ═══════════════════════════════════════════════════════════════════════════

const props = defineProps({
  /**
   * Variante del componente:
   * - 'toggle': Botón simple que alterna entre claro/oscuro
   * - 'dropdown': Menú desplegable con 3 opciones (claro, oscuro, sistema)
   * - 'switch': Switch visual con iconos
   */
  variant: {
    type: String,
    default: 'toggle',
    validator: (value) => ['toggle', 'dropdown', 'switch'].includes(value),
  },
  /**
   * Mostrar etiqueta de texto (solo para dropdown)
   */
  showLabel: {
    type: Boolean,
    default: false,
  },
});

// ═══════════════════════════════════════════════════════════════════════════
// STORE
// ═══════════════════════════════════════════════════════════════════════════

const themeStore = useThemeStore();

// ═══════════════════════════════════════════════════════════════════════════
// ESTADO LOCAL
// ═══════════════════════════════════════════════════════════════════════════

const isOpen = ref(false);

// ═══════════════════════════════════════════════════════════════════════════
// COMPONENTES DE ICONOS (Inline para evitar dependencias)
// ═══════════════════════════════════════════════════════════════════════════

const SunIcon = {
  render() {
    return h(
      'svg',
      {
        fill: 'none',
        stroke: 'currentColor',
        viewBox: '0 0 24 24',
        class: 'h-5 w-5',
      },
      [
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z',
        }),
      ]
    );
  },
};

const MoonIcon = {
  render() {
    return h(
      'svg',
      {
        fill: 'none',
        stroke: 'currentColor',
        viewBox: '0 0 24 24',
        class: 'h-5 w-5',
      },
      [
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z',
        }),
      ]
    );
  },
};

const ComputerIcon = {
  render() {
    return h(
      'svg',
      {
        fill: 'none',
        stroke: 'currentColor',
        viewBox: '0 0 24 24',
        class: 'h-5 w-5',
      },
      [
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        }),
      ]
    );
  },
};

// ═══════════════════════════════════════════════════════════════════════════
// OPCIONES DE TEMA
// ═══════════════════════════════════════════════════════════════════════════

const themeOptions = [
  { value: 'light', label: 'Claro', icon: SunIcon },
  { value: 'dark', label: 'Oscuro', icon: MoonIcon },
  { value: 'system', label: 'Sistema', icon: ComputerIcon },
];

// ═══════════════════════════════════════════════════════════════════════════
// COMPUTED
// ═══════════════════════════════════════════════════════════════════════════

const isDark = computed(() => themeStore.isDark);

const currentIcon = computed(() => {
  const option = themeOptions.find((o) => o.value === themeStore.theme);
  return option?.icon || SunIcon;
});

const currentLabel = computed(() => {
  const option = themeOptions.find((o) => o.value === themeStore.theme);
  return option?.label || 'Tema';
});

// ═══════════════════════════════════════════════════════════════════════════
// MÉTODOS
// ═══════════════════════════════════════════════════════════════════════════

const selectTheme = (value) => {
  themeStore.setTheme(value);
  isOpen.value = false;
};
</script>
