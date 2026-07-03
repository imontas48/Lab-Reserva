import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * COMPOSABLE: useEquipment
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Encapsula toda la lógica de obtención y gestión de datos de Equipment.
 *
 * RESPONSABILIDADES:
 * - Gestionar el estado reactivo de equipment
 * - Realizar peticiones a la API
 * - Manejar estados de carga y error
 * - Proporcionar métodos para CRUD operations
 *
 * PATRÓN:
 * Este composable sigue el patrón de "lógica reutilizable" de Vue 3,
 * permitiendo que múltiples componentes compartan la misma lógica de negocio
 * sin duplicar código.
 *
 * DATOS RELACIONALES:
 * Este composable maneja equipment con relaciones anidadas (lab, software).
 * La API debe retornar objetos con la estructura:
 * {
 *   id: 1,
 *   identifier: "LAB-PC-001",
 *   type: "desktop",
 *   is_operational: true,
 *   lab: { id: 1, name: "Laboratorio A" },
 *   software: [...]
 * }
 *
 * USO:
 * ```javascript
 * const { equipment, loading, error, fetchEquipment } = useEquipment();
 *
 * onMounted(() => {
 *   fetchEquipment();
 * });
 * ```
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
export function useEquipment() {
    // =========================================================================
    // ESTADO REACTIVO
    // =========================================================================

    /**
     * Lista de equipment
     * Almacena el array de equipment obtenido de la API
     *
     * ESTRUCTURA ESPERADA:
     * [
     *   {
     *     id: 1,
     *     identifier: "LAB-PC-001",
     *     type: "desktop",
     *     specifications: "Intel i7, 16GB RAM",
     *     is_operational: true,
     *     lab_id: 1,
     *     lab: { id: 1, name: "Laboratorio A", code: "LAB-A" },
     *     software: [...],
     *     created_at: "2024-01-15",
     *     updated_at: "2024-01-15"
     *   },
     *   ...
     * ]
     *
     * @type {Ref<Array>}
     */
    const equipment = ref([]);

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
     * Almacena errores de validación específicos por campo desde la API (422)
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
     * OBTENER LISTA DE EQUIPMENT
     * -------------------------------------------------------------------------
     *
     * Realiza una petición GET a la API para obtener todos los registros de equipment.
     *
     * FLUJO:
     * 1. Activa el estado de loading
     * 2. Limpia cualquier error previo
     * 3. Realiza la petición GET a /api/v1/equipment
     * 4. En caso de éxito, actualiza el array de equipment
     * 5. En caso de error, captura y almacena el mensaje de error
     * 6. Finalmente, desactiva el estado de loading
     *
     * ESTRUCTURA DE RESPUESTA ESPERADA:
     * {
     *   data: [
     *     {
     *       id: 1,
     *       identifier: "LAB-PC-001",
     *       type: "desktop",
     *       specifications: "Intel i7, 16GB RAM, 512GB SSD",
     *       is_operational: true,
     *       lab_id: 1,
     *       lab: {
     *         id: 1,
     *         name: "Laboratorio de Programación",
     *         code: "LAB-PROG-01"
     *       },
     *       software: [
     *         { id: 1, name: "Visual Studio Code", version: "1.85" },
     *         { id: 2, name: "Git", version: "2.43" }
     *       ],
     *       created_at: "2024-01-15T10:30:00.000000Z",
     *       updated_at: "2024-01-15T10:30:00.000000Z"
     *     },
     *     ...
     *   ]
     * }
     *
     * @returns {Promise<void>}
     */
    const fetchEquipment = async () => {
        // Activar estado de carga
        loading.value = true;

        // Limpiar errores previos
        error.value = null;

        try {
            console.log(' Obteniendo lista de equipment...');

            // Realizar petición GET a la API
            // La instancia apiClient ya tiene configurados:
            // - La baseURL (/api/v1)
            // - Los interceptores de CSRF
            // - El manejo de autenticación con Sanctum
            const response = await apiClient.get('/equipment');

            // Extraer los datos de la respuesta
            // Laravel API Resources típicamente devuelven los datos en response.data.data
            // Si no existe, usa response.data directamente
            equipment.value = response.data.data || response.data;

            console.log(` Equipment cargado: ${equipment.value.length} registros`);

            // Log de muestra para verificar estructura de datos relacionales
            if (equipment.value.length > 0) {
                console.log(' Muestra de estructura de datos:', equipment.value[0]);
            }
        } catch (err) {
            // Capturar y procesar el error
            console.error(' Error al cargar equipment:', err);

            // Construir mensaje de error amigable para el usuario
            if (err.response) {
                // El servidor respondió con un código de error
                switch (err.response.status) {
                    case 404:
                        error.value = 'No se encontró el endpoint de equipment. Contacta al administrador.';
                        break;
                    case 403:
                        error.value = 'No tienes permisos para ver los equipos.';
                        break;
                    case 500:
                        error.value = 'Error del servidor. Por favor, intenta nuevamente más tarde.';
                        break;
                    default:
                        error.value = 'Error al cargar los equipos. Por favor, intenta nuevamente.';
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
     * OBTENER EQUIPMENT POR ID
     * -------------------------------------------------------------------------
     *
     * Obtiene los detalles de un equipment específico por su ID.
     *
     * @param {Number} id - El ID del equipment a obtener
     * @returns {Promise<Object|null>} Los datos del equipment o null si hay error
     */
    const fetchEquipmentById = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            console.log(` Obteniendo equipment con ID: ${id}...`);

            const response = await apiClient.get(`/equipment/${id}`);
            const equipmentItem = response.data.data || response.data;

            console.log(' Equipment obtenido:', equipmentItem);

            return equipmentItem;
        } catch (err) {
            console.error(' Error al obtener equipment:', err);

            if (err.response?.status === 404) {
                error.value = 'Equipment no encontrado.';
            } else {
                error.value = 'Error al cargar los detalles del equipment.';
            }

            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * CREAR NUEVO EQUIPMENT
     * -------------------------------------------------------------------------
     *
     * Crea un nuevo registro de equipment en la base de datos.
     *
     * @param {Object} equipmentData - Los datos del nuevo equipment
     * @returns {Promise<Object|null>} El equipment creado o null si hay error
     */
    const createEquipment = async (equipmentData) => {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            console.log(' Creando nuevo equipment...', equipmentData);

            const response = await apiClient.post('/equipment', equipmentData);
            const newEquipment = response.data.data || response.data;

            // Agregar el nuevo equipment al inicio de la lista local
            equipment.value.unshift(newEquipment);

            console.log(' Equipment creado exitosamente:', newEquipment);

            return newEquipment;
        } catch (err) {
            console.error(' Error al crear equipment:', err);

            if (err.response?.status === 422) {
                // Error de validación - capturar errores específicos por campo
                validationErrors.value = err.response.data.errors || {};
                error.value = 'Datos de equipment inválidos. Verifica los campos.';
                console.log(' Errores de validación:', validationErrors.value);
            } else {
                error.value = 'Error al crear el equipment. Por favor, intenta nuevamente.';
            }

            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * ACTUALIZAR EQUIPMENT
     * -------------------------------------------------------------------------
     *
     * Actualiza un registro de equipment existente.
     *
     * @param {Number} id - El ID del equipment a actualizar
     * @param {Object} equipmentData - Los datos actualizados
     * @returns {Promise<Object|null>} El equipment actualizado o null si hay error
     */
    const updateEquipment = async (id, equipmentData) => {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            console.log(` Actualizando equipment con ID: ${id}...`, equipmentData);

            const response = await apiClient.put(`/equipment/${id}`, equipmentData);
            const updatedEquipment = response.data.data || response.data;

            // Actualizar el equipment en la lista local
            const index = equipment.value.findIndex(e => e.id === id);
            if (index !== -1) {
                equipment.value[index] = updatedEquipment;
            }

            console.log(' Equipment actualizado exitosamente:', updatedEquipment);

            return updatedEquipment;
        } catch (err) {
            console.error(' Error al actualizar equipment:', err);

            if (err.response?.status === 422) {
                // Error de validación - capturar errores específicos por campo
                validationErrors.value = err.response.data.errors || {};
                error.value = 'Datos de equipment inválidos. Verifica los campos.';
                console.log(' Errores de validación:', validationErrors.value);
            } else if (err.response?.status === 404) {
                error.value = 'Equipment no encontrado.';
            } else {
                error.value = 'Error al actualizar el equipment. Por favor, intenta nuevamente.';
            }

            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * -------------------------------------------------------------------------
     * ELIMINAR EQUIPMENT
     * -------------------------------------------------------------------------
     *
     * Elimina un registro de equipment de la base de datos.
     *
     * @param {Number} id - El ID del equipment a eliminar
     * @returns {Promise<Boolean>} true si se eliminó correctamente, false si hubo error
     */
    const deleteEquipment = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            console.log(` Eliminando equipment con ID: ${id}...`);

            await apiClient.delete(`/equipment/${id}`);

            // Remover el equipment de la lista local
            equipment.value = equipment.value.filter(e => e.id !== id);

            console.log(' Equipment eliminado exitosamente');

            return true;
        } catch (err) {
            console.error(' Error al eliminar equipment:', err);

            if (err.response?.status === 404) {
                error.value = 'Equipment no encontrado.';
            } else if (err.response?.status === 403) {
                error.value = 'No tienes permisos para eliminar este equipment.';
            } else {
                error.value = 'Error al eliminar el equipment. Por favor, intenta nuevamente.';
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
     * Alias de fetchEquipment para mayor claridad semántica.
     * Útil cuando se quiere explícitamente "refrescar" la lista.
     */
    const refresh = () => {
        return fetchEquipment();
    };

    // =========================================================================
    // RETORNO DE LA API PÚBLICA DEL COMPOSABLE
    // =========================================================================

    /**
     * API pública del composable
     *
     * Expone el estado reactivo y los métodos necesarios para que
     * los componentes puedan gestionar equipment.
     */
    return {
        // Estado reactivo
        equipment,
        loading,
        error,
        validationErrors,

        // Métodos
        fetchEquipment,
        fetchEquipmentById,
        createEquipment,
        updateEquipment,
        deleteEquipment,
        clearErrors,
        refresh
    };
}
