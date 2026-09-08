<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      @click.self="close"
      @keydown.esc="close"
    >
      <div :class="['w-full rounded-lg bg-white shadow-xl dark:bg-gray-800', widthClass]">
        <header v-if="title" class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h2>
        </header>

        <div class="px-6 py-4">
          <slot />
        </div>

        <footer v-if="$slots.footer" class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Diálogo modal.
 *
 * Había tres implementaciones distintas escritas a mano con `fixed inset-0`.
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue']);

const widthClass = computed(() => ({
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-3xl',
}[props.size]));

function close() {
  if (props.closeOnBackdrop) {
    emit('update:modelValue', false);
  }
}
</script>
