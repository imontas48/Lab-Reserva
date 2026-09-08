/**
 * Directiva v-click-outside.
 *
 * Estaba definida como directiva local dentro de AppLayout, mientras que
 * ThemeToggle la necesitaba y en su lugar usaba `@click.outside`, que NO es un
 * modificador de Vue: el compilador lo descarta y deja un `@click` normal sobre
 * el propio menú. El efecto era el contrario del buscado — pulsar DENTRO lo
 * cerraba y pulsar fuera no lo cerraba nunca.
 */
export const vClickOutside = {
    mounted(el, binding) {
        el._clickOutsideHandler = (event) => {
            if (!el.contains(event.target)) {
                binding.value(event);
            }
        };
        document.addEventListener('click', el._clickOutsideHandler);
    },
    unmounted(el) {
        document.removeEventListener('click', el._clickOutsideHandler);
        delete el._clickOutsideHandler;
    },
};
