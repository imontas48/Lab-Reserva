import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * ════════════════════════════════════════════════════            
            const response = await apiClient.get(`/v1/labs/${id}`);

            const lab = response.data.data || response.data;═══════════════════
 * COMPOSABLE: useLabs
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Encapsula toda la lógica de obtención y gestión de datos de Laboratorios.
 *
 * RESPONSABILIDADES:
 * - Gestionar el estado reactivo de labs
 * - Realizar peticiones a la API
 * - Manejar estados de carga y error
 * - Proporcionar métodos para CRUD operations
 * - Gestionar errores de validación (422)
 *
 * PATRÓN:
 * Este composable sigue el patrón de "lógica reutilizable" de Vue 3,
 * permitiendo que múltiples componentes compartan la misma lógica de negocio
 * sin duplicar código.
 *
 * USO:
 * ```javascript
 * const { labs, loading, error, fetchLabs, createLab } = useLabs();
 *
 * onMounted(() => {
 *   fetchLabs();
 * });
 * ```
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
export function useLabs() {
    // =========================================================================
    // ESTADO REACTIVO
    // =========================================================================

    /**
     * Lista de laboratorios
     * Almacena el array de labs obtenido de la API
     *
     * @type {Ref<Array>}
     */
    const labs = ref([]);

    /**
     * Estado de carga
     * Indica si hay una operación en curso
     * Inicializado en true para mostrar el skeleton loader al montar el componente
     *
     * @type {Ref<Boolean>}
     */
    const loading = ref(true);

    /**
     * Error general
     * Almacena mensajes de error de la API o errores de red
     *
     * @type {Ref<String|null>}
     */
    const error = ref(null);

    /**
     * Errores de validación
     * Almacena errores de validación del backend (422)
     * Estructura: { field_name: ['error message 1', 'error message 2'] }
     *
     * @type {Ref<Object>}
     */
    const validationErrors = ref({});

    // =========================================================================
    // MÉTODOS - LECTURA
    // =========================================================================

    /**
     * Obtener todos los laboratorios
     *
     * Realiza una petición GET a /api/labs para obtener la lista completa
     * de laboratorios.
     *
     * @returns {Promise<void>}
     *
     * @example
     * await fetchLabs();
     *  // [{ id: 1, name: 'Lab A', ... }, ...]
     */
    const fetchLabs = async () => {
        try {
            loading.value = true;
            error.value = null;

            const response = await apiClient.get('/labs');


            labs.value = response.data.data || response.data;
        } catch (err) {
            console.error(' Error fetching labs:', err);
            error.value = err.response?.data?.message || 'Error al cargar los laboratorios';
        } finally {
            loading.value = false;
        }
    };

    /**
     * Obtener un laboratorio por ID
     *
     * Realiza una petición GET a /api/labs/:id para obtener los detalles
     * de un laboratorio específico.
     *
     * @param {Number|String} id - ID del laboratorio
     * @returns {Promise<Object>} - Datos del laboratorio
     *
     * @example
     * const lab = await fetchLabById(1);
     *  // { id: 1, name: 'Lab A', location: 'Building A', ... }
     */
    const fetchLabById = async (id) => {
        try {
            loading.value = true;
            error.value = null;

            const response = await apiClient.get(`/labs/${id}`);

            const lab = response.data.data || response.data;

            return lab;
        } catch (err) {
            console.error(` Error fetching lab ${id}:`, err);
            error.value = err.response?.data?.message || 'Error al cargar el laboratorio';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    // =========================================================================
    // MÉTODOS - CREACIÓN
    // =========================================================================

    /**
     * Crear un nuevo laboratorio
     *
     * Realiza una petición POST a /api/labs con los datos del laboratorio.
     *
     * @param {Object} labData - Datos del laboratorio a crear
     * @param {String} labData.name - Nombre del laboratorio (requerido)
     * @param {String} labData.location - Ubicación del laboratorio (requerido)
     * @param {String} labData.description - Descripción (opcional)
     * @returns {Promise<Object>} - Laboratorio creado
     *
     * @throws {Error} - Si hay errores de validación (422) o errores de red
     *
     * @example
     * try {
     *   const newLab = await createLab({
     *     name: 'Laboratorio de Redes',
     *     location: 'Edificio C, Piso 2',
     *     description: 'Laboratorio equipado para prácticas de redes'
     *   });
     *   
     * } catch (error) {
     *   console.error('Error de validación:', validationErrors.value);
     * }
     */
    const createLab = async (labData) => {
        try {
            loading.value = true;
            error.value = null;
            validationErrors.value = {};

            const response = await apiClient.post('/labs', labData);

            const newLab = response.data.data || response.data;

            // Agregar el nuevo laboratorio a la lista local
            labs.value.unshift(newLab);

            return newLab;
        } catch (err) {
            console.error(' Error creating lab:', err);

            // Manejar errores de validación (422)
            if (err.response?.status === 422) {
                validationErrors.value = err.response.data.errors || {};
                error.value = 'Por favor, corrige los errores en el formulario';
            } else {
                error.value = err.response?.data?.message || 'Error al crear el laboratorio';
            }

            throw err;
        } finally {
            loading.value = false;
        }
    };

    // =========================================================================
    // MÉTODOS - ACTUALIZACIÓN
    // =========================================================================

    /**
     * Actualizar un laboratorio existente
     *
     * Realiza una petición PUT a /api/labs/:id con los datos actualizados.
     *
     * @param {Number|String} id - ID del laboratorio a actualizar
     * @param {Object} labData - Datos del laboratorio a actualizar
     * @returns {Promise<Object>} - Laboratorio actualizado
     *
     * @throws {Error} - Si hay errores de validación (422) o errores de red
     *
     * @example
     * try {
     *   const updatedLab = await updateLab(1, {
     *     name: 'Laboratorio de Redes Avanzadas',
     *     location: 'Edificio C, Piso 3'
     *   });
     *   
     * } catch (error) {
     *   console.error('Error de validación:', validationErrors.value);
     * }
     */
    const updateLab = async (id, labData) => {
        try {
            loading.value = true;
            error.value = null;
            validationErrors.value = {};

            const response = await apiClient.put(`/labs/${id}`, labData);

            const updatedLab = response.data.data || response.data;

            // Actualizar el laboratorio en la lista local
            const index = labs.value.findIndex(lab => lab.id === id);
            if (index !== -1) {
                labs.value[index] = updatedLab;
            }

            return updatedLab;
        } catch (err) {
            console.error(` Error updating lab ${id}:`, err);

            // Manejar errores de validación (422)
            if (err.response?.status === 422) {
                validationErrors.value = err.response.data.errors || {};
                error.value = 'Por favor, corrige los errores en el formulario';
            } else {
                error.value = err.response?.data?.message || 'Error al actualizar el laboratorio';
            }

            throw err;
        } finally {
            loading.value = false;
        }
    };

    // =========================================================================
    // MÉTODOS - ELIMINACIÓN
    // =========================================================================

    /**
     * Eliminar un laboratorio
     *
     * Realiza una petición DELETE a /api/labs/:id.
     *
     * @param {Number|String} id - ID del laboratorio a eliminar
     * @returns {Promise<void>}
     *
     * @example
     * await deleteLab(1);
     * 
     */
    const deleteLab = async (id) => {
        try {
            loading.value = true;
            error.value = null;

            await apiClient.delete(`/labs/${id}`);


            // Eliminar el laboratorio de la lista local
            labs.value = labs.value.filter(lab => lab.id !== id);
        } catch (err) {
            console.error(` Error deleting lab ${id}:`, err);
            error.value = err.response?.data?.message || 'Error al eliminar el laboratorio';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    // =========================================================================
    // MÉTODOS - UTILIDADES
    // =========================================================================

    /**
     * Limpiar errores
     *
     * Resetea tanto el error general como los errores de validación,
     * así como el estado de carga.
     *
     * @example
     * clearErrors();
     */
    const clearErrors = () => {
        error.value = null;
        validationErrors.value = {};
        loading.value = false; //  FIX: También resetear loading
    };

    /**
     * Refrescar la lista de laboratorios
     *
     * Alias de fetchLabs() para mayor claridad semántica.
     *
     * @returns {Promise<void>}
     *
     * @example
     * await refresh();
     */
    const refresh = () => fetchLabs();

    // =========================================================================
    // RETORNO PÚBLICO
    // =========================================================================

    return {
        // Estado
        labs,
        loading,
        error,
        validationErrors,

        // Métodos de lectura
        fetchLabs,
        fetchLabById,

        // Métodos de escritura
        createLab,
        updateLab,
        deleteLab,

        // Utilidades
        clearErrors,
        refresh,
    };
}
