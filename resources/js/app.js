import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import axios from 'axios';
import Welcome from './components/Welcome.vue';

// Configuración global de Axios
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Crear instancia de Pinia
const pinia = createPinia();

// Crear aplicación Vue
const app = createApp(Welcome);

// Usar Pinia
app.use(pinia);

// Auto-registro de componentes (opcional - puedes importar manualmente)
// const components = import.meta.glob('./components/**/*.vue', { eager: true });
// Object.entries(components).forEach(([path, component]) => {
//     const componentName = path.split('/').pop().replace(/\.\w+$/, '');
//     app.component(componentName, component.default);
// });

// Montar la aplicación
app.mount('#app');
