import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * COMPOSABLE: useSoftware
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Encapsula toda la lógica de obtención y gestión de datos de Software.
 *
 * RESPONSABILIDADES:
 * - Gestionar el estado reactivo de software
 * - Realizar peticiones a la API
 * - Manejar estados de carga y error
 * - Proporcionar métodos para CRUD operations
 *
 * PATRÓN:
 * Este composable sigue el patrón de "lógica reutilizable" de Vue 3,
 * permitiendo que múltiples componentes compartan la misma lógica de negocio
 * sin duplicar código.
 *
 * USO:
 * ```javascript
 * const { software, loading, error, fetchSoftware } = useSoftware();
 *
 * onMounted(() => {
 *   fetchSoftware();
 * });
 * ```
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
export function useSoftware() {
    // =========================================================================
    // ESTADO REACTIVO
    // =========================================================================

    /**
     * Lista de software
     * Almacena el array de software obtenido de la API
     *
     * @type {Ref<Array>}
     */
    const software = ref([]);

    /**
     * Estado de carga
     * Indica si hay una operación en curso
     * Inicializado en true para mostrar el skeleton loader al montar el componente
     *
     * @type {Ref<Boolean>}
     */
    const loading = ref(true);

    /**
     * Mensaje de error
     * Almacena mensajes de error si alguna operación falla
     * null indica que no hay errores
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
    // MÉTODOS PÚBLICOS
    // =========================================================================

    /**
     * -------------------------------------------------------------------------
     * OBTENER LISTA DE SOFTWARE
     * -------------------------------------------------------------------------
     *
     * Realiza una petición GET a la API para obtener todos los registros de software.
     *
     * FLUJO:
     * 1. Activa el estado de loading
     * 2. Limpia cualquier error previo
     * 3. Realiza la petición GET a /api/v1/software
     * 4. En caso de éxito, actualiza el array de software
     * 5. En caso de error, captura y almacena el mensaje de error
     * 6. Finalmente, desactiva el estado de loading
     *
     * ESTRUCTURA DE RESPUESTA ESPERADA:
     * {
     *   data: [
     *     {
     *       id: 1,
     *       name: "Adobe Photoshop",
     *       version: "2024",
     *       license_type: "commercial",
     *       equipment_count: 15,
     *       created_at: "2024-01-15",
     *       updated_at: "2024-01-15"
     *     },
     *     ...
     *   ]
     * }
     *
     * @returns {Promise<void>}
     */
    const fetchSoftware = async () => {
        // Activar estado de carga
        loading.value = true;

        // Limpiar errores previos
        error.value = null;

        try {
            console.log('🔄 Obteniendo lista de software...');

            // Realizar petición GET a la API
            // La instancia apiClient ya tiene configurados:
            // - La baseURL (/api/v1)
            // - Los interceptores de CSRF
            // - El manejo de autenticación con Sanctum
            const response = await apiClient.get('/v1/software');

            // Extraer los datos de la respuesta
            // Laravel API Resources típicamente devuelven los datos en response.data.data
            // Si no existe, usa response.data directamente
            software.value = response.data.data || response.data;

            console.log(`✅ Software cargado: ${software.value.length} registros`);
        } catch (err) {
            // Capturar y procesar el error
            console.error('❌ Error al cargar software:', err);

            // Construir mensaje de error amigable para el usuario
            if (err.response) {
                // El servidor respondió con un código de error
                switch (err.response.status) {
                    case 404:
                        error.value = 'No se encontró el endpoint de software. Contacta al administrador.';
                        break;
                    case 403:
                        error.value = 'No tienes permisos para ver el software.';
                        break;
                    case 500:
                        error.value = 'Error del servidor. Por favor, intenta nuevamente más tarde.';
                        break;
                    default:
                        error.value = 'Error al cargar el software. Por favor, intenta nuevamente.';
                }
            } else if (err.request) {
                // La petición se hizo pero no hubo respuesta
                error.value = 'No se pudo conectar con el servidor. Verifica tu conexión a internet.';
            } else {
                // Error al configurar la petición
                error.value = 'Error inesperado. Por favor, intenta nuevamente.';
            }
        } finally {
            // Siempre desactivar el estado de carga, independientemente del resultado
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * OBTENER SOFTWARE POR ID
     * -------------------------------------------------------------------------
     *
     * Obtiene los detalles de un software específico por su ID.
     *
     * @param {Number} id - El ID del software a obtener
     * @returns {Promise<Object|null>} Los datos del software o null si hay error
     */
    const fetchSoftwareById = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            console.log(`🔄 Obteniendo software con ID: ${id}...`);

            const response = await apiClient.get(`/software/${id}`);
            const softwareItem = response.data.data || response.data;

            console.log('✅ Software obtenido:', softwareItem);

            return softwareItem;
        } catch (err) {
            console.error('❌ Error al obtener software:', err);

            if (err.response?.status === 404) {
                error.value = 'Software no encontrado.';
            } else {
                error.value = 'Error al cargar los detalles del software.';
            }

            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * CREAR NUEVO SOFTWARE
     * -------------------------------------------------------------------------
     *
     * Crea un nuevo registro de software en la base de datos.
     *
     * @param {Object} softwareData - Los datos del nuevo software
     * @returns {Promise<Object|null>} El software creado o null si hay error
     */
    const createSoftware = async (softwareData) => {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            console.log('� Creando nuevo software:', softwareData);

            const response = await apiClient.post('/v1/software', softwareData);
            const newSoftware = response.data.data || response.data;

            // Agregar el nuevo software a la lista local
            software.value.unshift(newSoftware);

            console.log('✅ Software creado exitosamente:', newSoftware);

            return newSoftware;
        } catch (err) {
            console.error('❌ Error al crear software:', err);

            // Manejar errores de validación (422)
            if (err.response?.status === 422) {
                validationErrors.value = err.response.data.errors || {};
                error.value = 'Por favor, corrige los errores en el formulario';
                console.log('📋 Validation errors:', validationErrors.value);
            } else {
                error.value = err.response?.data?.message || 'Error al crear el software';
            }

            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * ACTUALIZAR SOFTWARE
     * -------------------------------------------------------------------------
     *
     * Actualiza un registro de software existente.
     *
     * @param {Number} id - El ID del software a actualizar
     * @param {Object} softwareData - Los datos actualizados
     * @returns {Promise<Object|null>} El software actualizado o null si hay error
     */
    const updateSoftware = async (id, softwareData) => {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            console.log(`� Actualizando software ${id}:`, softwareData);

            const response = await apiClient.put(`/software/${id}`, softwareData);
            const updatedSoftware = response.data.data || response.data;

            // Actualizar el software en la lista local
            const index = software.value.findIndex(s => s.id === id);
            if (index !== -1) {
                software.value[index] = updatedSoftware;
            }

            console.log('✅ Software actualizado exitosamente:', updatedSoftware);

            return updatedSoftware;
        } catch (err) {
            console.error(`❌ Error al actualizar software ${id}:`, err);

            // Manejar errores de validación (422)
            if (err.response?.status === 422) {
                validationErrors.value = err.response.data.errors || {};
                error.value = 'Por favor, corrige los errores en el formulario';
                console.log('📋 Validation errors:', validationErrors.value);
            } else {
                error.value = err.response?.data?.message || 'Error al actualizar el software';
            }

            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * ELIMINAR SOFTWARE
     * -------------------------------------------------------------------------
     *
     * Elimina un registro de software de la base de datos.
     *
     * @param {Number} id - El ID del software a eliminar
     * @returns {Promise<Boolean>} true si se eliminó correctamente, false si hubo error
     */
    const deleteSoftware = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            console.log(`🔄 Eliminando software con ID: ${id}...`);

            await apiClient.delete(`/software/${id}`);

            // Remover el software de la lista local
            software.value = software.value.filter(s => s.id !== id);

            console.log('✅ Software eliminado exitosamente');

            return true;
        } catch (err) {
            console.error('❌ Error al eliminar software:', err);

            if (err.response?.status === 404) {
                error.value = 'Software no encontrado.';
            } else if (err.response?.status === 403) {
                error.value = 'No tienes permisos para eliminar este software.';
            } else {
                error.value = 'Error al eliminar el software. Por favor, intenta nuevamente.';
            }

            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * LIMPIAR ERRORES
     * -------------------------------------------------------------------------
     *
     * Limpia el mensaje de error actual y los errores de validación.
     * Útil para cerrar notificaciones de error en la UI.
     */
    const clearErrors = () => {
        error.value = null;
        validationErrors.value = {};
    };

    /**
     * -------------------------------------------------------------------------
     * REFRESCAR LISTA
     * -------------------------------------------------------------------------
     *
     * Alias de fetchSoftware para mayor claridad semántica.
     * Útil cuando se quiere explícitamente "refrescar" la lista.
     */
    const refresh = () => {
        return fetchSoftware();
    };

    // =========================================================================
    // RETORNO DE LA API PÚBLICA DEL COMPOSABLE
    // =========================================================================

    /**
     * API pública del composable
     *
     * Expone el estado reactivo y los métodos necesarios para que
     * los componentes puedan gestionar software.
     */
    return {
        // Estado reactivo
        software,
        loading,
        error,
        validationErrors,

        // Métodos
        fetchSoftware,
        fetchSoftwareById,
        createSoftware,
        updateSoftware,
        deleteSoftware,
        clearErrors,
        refresh
    };
}
