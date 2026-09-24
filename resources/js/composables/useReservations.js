/**
 * useReservations.js
 *
 * Composable para la gestión completa de reservas de equipos.
 * Este es el MOTOR LÓGICO central para todas las operaciones de reserva
 * que realiza un usuario final en la aplicación.
 *
 * Responsabilidades:
 * - Consultar disponibilidad de equipos por fecha
 * - Crear nuevas reservas
 * - Obtener reservas del usuario autenticado
 * - Cancelar reservas existentes
 * - Manejar estados de carga y errores de forma centralizada
 *
 * @author El Arquitecto - Lab-Reserva Team
 * @version 1.0.0
 */

import { ref } from 'vue';
import api from '@/utils/api';

/**
 * Composable de Reservas
 *
 * Proporciona estado reactivo y métodos para gestionar reservas.
 * Sigue el principio de responsabilidad única (SRP).
 */
export function useReservations() {
  // ============================================================================
  // ESTADO REACTIVO
  // ============================================================================

  /**
   * Lista de reservas
   * @type {Ref<Array>}
   */
  const reservations = ref([]);

  /**
   * Metadatos de paginación para el listado actual (by-role)
   * @type {Ref<Object|null>}
   */
  const paginationMeta = ref(null);

  /**
   * Estado de carga para operaciones asíncronas
   * @type {Ref<boolean>}
   */
  const loading = ref(false);

  /**
   * Error general de la API
   * @type {Ref<string|null>}
   */
  const error = ref(null);

  /**
   * Errores de validación específicos del backend (422)
   * Estructura: { field_name: ['Error message 1', 'Error message 2'] }
   * @type {Ref<Object>}
   */
  const validationErrors = ref({});

  // ============================================================================
  // MÉTODOS PRIVADOS (HELPERS)
  // ============================================================================

  /**
   * Limpia todos los estados de error
   * Se llama antes de cada operación para reset del estado
   */
  const clearErrors = () => {
    error.value = null;
    validationErrors.value = {};
  };

  /**
   * Maneja errores de la API de forma centralizada
   *
   * @param {Error} err - Error capturado de Axios
   * @param {string} defaultMessage - Mensaje por defecto si no hay mensaje específico
   */
  const handleApiError = (err, defaultMessage = 'Error al procesar la solicitud') => {
    console.error(' Error en useReservations:', err);

    // Error 422: Validación del backend
    if (err.response?.status === 422) {
      validationErrors.value = err.response.data.errors || {};
      error.value = err.response.data.message || 'Error de validación';
      return;
    }

    // Error 403: No autorizado
    if (err.response?.status === 403) {
      error.value = 'No tienes permisos para realizar esta acción';
      return;
    }

    // Error 404: Recurso no encontrado
    if (err.response?.status === 404) {
      error.value = 'Recurso no encontrado';
      return;
    }

    // Error 409: Conflicto (ej. horario ya reservado)
    if (err.response?.status === 409) {
      error.value = err.response.data.message || 'Este horario ya no está disponible';
      return;
    }

    // Otros errores
    error.value = err.response?.data?.message || defaultMessage;
  };

  // ============================================================================
  // MÉTODOS PÚBLICOS (ACTIONS)
  // ============================================================================

  /**
   * Obtiene las reservas confirmadas de un equipo específico en un rango de fechas
   *
   * Esta función es CRÍTICA para el calendario de disponibilidad.
   * Permite mostrar qué horarios están ocupados para un equipo dado.
   *
   * @param {number} equipmentId - ID del equipo a consultar
   * @param {string} startDate - Fecha de inicio (formato: YYYY-MM-DD)
   * @param {string} endDate - Fecha de fin (formato: YYYY-MM-DD)
   * @returns {Promise<boolean>} - true si se cargaron con éxito, false si hubo error
   *
   * @example
   * const success = await fetchReservationsForEquipment(5, '2025-10-15', '2025-10-22');
   * if (success) {
   *   
   * }
   */
  const fetchReservationsForEquipment = async (equipmentId, startDate, endDate) => {
    clearErrors();
    loading.value = true;

    try {

      // Petición GET a la ruta anidada de Laravel
      const response = await api.get(`/equipment/${equipmentId}/reservations`, {
        params: {
          start_date: startDate,
          end_date: endDate
        }
      });

      // Poblar el ref con los datos recibidos
      reservations.value = response.data.data || response.data;

      return true;

    } catch (err) {
      handleApiError(err, 'Error al cargar las reservas del equipo');
      reservations.value = [];
      return false;

    } finally {
      loading.value = false;
    }
  };

  /**
   * Crea una nueva reserva
   *
   * Validación robusta en el backend. Los errores 422 poblarán validationErrors.
   *
   * @param {Object} payload - Datos de la reserva
   * @param {number} payload.equipment_id - ID del equipo a reservar
   * @param {string} payload.start_time - Fecha/hora de inicio (ISO 8601 o YYYY-MM-DD HH:mm:ss)
   * @param {string} payload.end_time - Fecha/hora de fin (ISO 8601 o YYYY-MM-DD HH:mm:ss)
   * @param {string} [payload.purpose] - Propósito de la reserva (opcional)
   * @returns {Promise<Object|null>} - Objeto de reserva creada o null si falló
   *
   * @example
   * const reservation = await createReservation({
   *   equipment_id: 5,
   *   start_time: '2025-10-15 10:00:00',
   *   end_time: '2025-10-15 12:00:00',
   *   purpose: 'Práctica de laboratorio'
   * });
   *
   * if (reservation) {
   *   
   * } else {
   *   
   * }
   */
  const createReservation = async (payload) => {
    clearErrors();
    loading.value = true;

    try {

      // Petición POST al endpoint de reservas
      const response = await api.post('/reservations', payload);

      const newReservation = response.data.data || response.data;


      // Agregar la nueva reserva a la lista local (útil para actualizar UI inmediatamente)
      reservations.value.push(newReservation);

      return newReservation;

    } catch (err) {
      handleApiError(err, 'Error al crear la reserva');
      return null;

    } finally {
      loading.value = false;
    }
  };

  /**
   * Obtiene todas las reservas del usuario autenticado
   *
   * Útil para mostrar "Mis Reservas" en el perfil del usuario.
   *
   * @returns {Promise<boolean>} - true si se cargaron con éxito, false si hubo error
   *
   * @example
   * const success = await fetchMyReservations();
   * if (success) {
   *   
   * }
   */
  const fetchMyReservations = async () => {
    clearErrors();
    loading.value = true;

    try {

      // Petición GET al endpoint personalizado
      const response = await api.get('/my-reservations');

      reservations.value = response.data.data || response.data;

      return true;

    } catch (err) {
      handleApiError(err, 'Error al cargar tus reservas');
      reservations.value = [];
      return false;

    } finally {
      loading.value = false;
    }
  };

  /**
   * Cancela una reserva del usuario autenticado
   *
   * Solo el propietario de la reserva o un admin puede cancelarla.
   * La validación de permisos se hace en el backend (Policy).
   *
   * @param {number} reservationId - ID de la reserva a cancelar
   * @returns {Promise<boolean>} - true si se canceló con éxito, false si hubo error
   *
   * @example
   * const success = await cancelMyReservation(123);
   * if (success) {
   *   
   *   // Actualizar la lista local
   *   await fetchMyReservations();
   * }
   */
  const cancelMyReservation = async (reservationId) => {
    clearErrors();
    loading.value = true;

    try {

      // Petición PATCH al endpoint de cancelación
      await api.patch(`/reservations/${reservationId}/cancel`);


      // Actualizar la lista local removiendo o marcando la reserva como cancelada
      const index = reservations.value.findIndex(r => r.id === reservationId);
      if (index !== -1) {
        // Opción 1: Remover de la lista
        reservations.value.splice(index, 1);

        // Opción 2: Marcar como cancelada (descomentar si el backend devuelve el estado)
        // reservations.value[index].status = 'cancelled';
      }

      return true;

    } catch (err) {
      handleApiError(err, 'Error al cancelar la reserva');
      return false;

    } finally {
      loading.value = false;
    }
  };

  // ============================================================================
  // RETORNO DEL COMPOSABLE
  // ============================================================================

  /**
   * Obtiene todas las reservas de usuarios con un rol específico (solo admin).
   *
   * @param {'student'|'teacher'} role - Rol de los usuarios cuyas reservas se quieren ver.
   * @param {Object} params - Parámetros opcionales (status, search, start_date, end_date, etc.)
   * @returns {Promise<boolean>} - true si se cargaron con éxito, false si hubo error
   */
  const fetchReservationsByRole = async (role, params = {}) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.get(`/reservations/by-role/${role}`, { params });
      reservations.value = response.data.data || response.data;
      paginationMeta.value = response.data.meta || null;
      return true;

    } catch (err) {
      handleApiError(err, `Error al cargar las reservas de ${role === 'student' ? 'estudiantes' : 'maestros'}`);
      reservations.value = [];
      paginationMeta.value = null;
      return false;

    } finally {
      loading.value = false;
    }
  };

  // ============================================================================
  // RESERVA DE LABORATORIO COMPLETO Y FLUJO DE APROBACIÓN
  //
  // Mismo dialecto que el resto del archivo (devuelven el objeto o null, o un
  // booleano) para no mezclar dos contratos en un mismo composable. Deuda
  // anotada: migrar useReservations entero a useResource (que lanza).
  // ============================================================================

  /**
   * Ocupación de un laboratorio: sus clases y las reservas de sus equipos.
   *
   * @param {number} labId
   * @param {string} startDate - YYYY-MM-DD
   * @param {string} endDate - YYYY-MM-DD
   * @returns {Promise<boolean>}
   */
  const fetchReservationsForLab = async (labId, startDate, endDate) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.get(`/labs/${labId}/reservations`, {
        params: { start_date: startDate, end_date: endDate }
      });

      reservations.value = response.data.data || response.data;

      return true;
    } catch (err) {
      handleApiError(err, 'Error al cargar la ocupación del laboratorio');
      reservations.value = [];
      return false;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Solicita un laboratorio completo para una clase. Nace pendiente de
   * aprobación salvo que quien la crea pueda aprobarla.
   *
   * @param {{ lab_id: number, start_time: string, end_time: string, purpose: string }} payload
   * @returns {Promise<Object|null>}
   */
  const createLabReservation = async (payload) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.post('/lab-reservations', payload);
      const newReservation = response.data.data || response.data;

      reservations.value.push(newReservation);

      return newReservation;
    } catch (err) {
      handleApiError(err, 'Error al solicitar el laboratorio');
      return null;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Cola de solicitudes pendientes (requiere reservations.approve).
   *
   * @param {Object} params - page, search, lab_id, etc.
   * @returns {Promise<boolean>}
   */
  const fetchPendingReservations = async (params = {}) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.get('/reservations/pending', { params });
      reservations.value = response.data.data || response.data;
      paginationMeta.value = response.data.meta || null;
      return true;
    } catch (err) {
      handleApiError(err, 'Error al cargar las solicitudes pendientes');
      reservations.value = [];
      paginationMeta.value = null;
      return false;
    } finally {
      loading.value = false;
    }
  };

  /**
   * @param {number} reservationId
   * @returns {Promise<Object|null>} la reserva ya confirmada, o null
   */
  const approveReservation = async (reservationId) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.patch(`/reservations/${reservationId}/approve`);
      const approved = response.data.data || response.data;

      replaceLocal(approved);

      return approved;
    } catch (err) {
      handleApiError(err, 'No se pudo aprobar la solicitud');
      return null;
    } finally {
      loading.value = false;
    }
  };

  /**
   * @param {number} reservationId
   * @param {string} reason - motivo obligatorio que verá el solicitante
   * @returns {Promise<Object|null>} la reserva rechazada, o null
   */
  const rejectReservation = async (reservationId, reason) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.patch(`/reservations/${reservationId}/reject`, { reason });
      const rejected = response.data.data || response.data;

      replaceLocal(rejected);

      return rejected;
    } catch (err) {
      handleApiError(err, 'No se pudo rechazar la solicitud');
      return null;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Serie semanal de clases. Devuelve { recurrence_group, created, skipped }
   * o null si falló.
   *
   * @param {{ lab_id, start_time, end_time, purpose, repeat_until, weekdays? }} payload
   */
  const createRecurringLabReservation = async (payload) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.post('/lab-reservations/recurring', payload);

      return response.data.data;
    } catch (err) {
      handleApiError(err, 'Error al crear la serie de clases');
      return null;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Registra la llegada. El código es obligatorio para el dueño.
   *
   * @returns {Promise<Object|null>}
   */
  const checkIn = async (reservationId, code = null) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.post(`/reservations/${reservationId}/check-in`, code ? { code } : {});
      const updated = response.data.data || response.data;

      replaceLocal(updated);

      return updated;
    } catch (err) {
      handleApiError(err, 'No se pudo registrar la llegada');
      return null;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Marca una inasistencia (requiere reservations.approve).
   *
   * @returns {Promise<Object|null>}
   */
  const markNoShow = async (reservationId) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.post(`/reservations/${reservationId}/no-show`);
      const updated = response.data.data || response.data;

      replaceLocal(updated);

      return updated;
    } catch (err) {
      handleApiError(err, 'No se pudo registrar la inasistencia');
      return null;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Cancela las ocurrencias futuras de la serie a la que pertenece la reserva.
   *
   * @returns {Promise<number|null>} cuántas se cancelaron, o null si falló
   */
  const cancelSeries = async (reservationId) => {
    clearErrors();
    loading.value = true;

    try {
      const response = await api.patch(`/reservations/${reservationId}/cancel-series`);

      return response.data.data?.cancelled ?? 0;
    } catch (err) {
      handleApiError(err, 'No se pudo cancelar la serie');
      return null;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Sustituye en la lista local la reserva que el servidor acaba de devolver.
   */
  const replaceLocal = (updated) => {
    const index = reservations.value.findIndex(r => r.id === updated.id);
    if (index !== -1) {
      reservations.value.splice(index, 1, updated);
    }
  };

  return {
    // Estado reactivo
    reservations,
    loading,
    error,
    validationErrors,
    paginationMeta,

    // Métodos
    fetchReservationsForEquipment,
    fetchReservationsForLab,
    createReservation,
    createLabReservation,
    fetchMyReservations,
    cancelMyReservation,
    fetchReservationsByRole,
    fetchPendingReservations,
    approveReservation,
    rejectReservation,
    createRecurringLabReservation,
    checkIn,
    markNoShow,
    cancelSeries,
  };
}
