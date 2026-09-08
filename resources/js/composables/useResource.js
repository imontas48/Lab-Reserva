import { ref } from 'vue';
import apiClient from '@/utils/api';

/**
 * CRUD genérico sobre un recurso de la API.
 *
 * `useLabs`, `useEquipment` y `useSoftware` sumaban 1.195 líneas con las mismas
 * diez funciones escritas tres veces. Y las tres copias divergían justo en lo
 * que importa:
 *
 *  - `deleteLab` lanzaba, `deleteEquipment` y `deleteSoftware` devolvían `false`.
 *    Por eso `EquipmentIndexView` mostraba un toast de ÉXITO tras un borrado
 *    fallido: su `catch` era inalcanzable.
 *  - `fetchById` lanzaba en uno y devolvía `null` en los otros, de modo que una
 *    vista accedía a `.name` sobre `null` si la carga fallaba.
 *  - `clearErrors` reseteaba `loading` solo en uno.
 *
 * CONTRATO ÚNICO: **todo error se lanza**. La vista decide qué mostrar; el
 * composable nunca traga un fallo ni lo convierte en un valor de retorno que
 * el llamante pueda ignorar por descuido.
 *
 * @param {string} endpoint  Ruta del recurso, sin barra inicial (ej. 'labs')
 * @param {{ singular?: string, plural?: string }} labels Textos para los mensajes
 */
export function useResource(endpoint, labels = {}) {
    const singular = labels.singular ?? 'registro';
    const plural = labels.plural ?? 'registros';

    const items = ref([]);
    const item = ref(null);
    const meta = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const validationErrors = ref({});

    /**
     * Normaliza el error de Axios y lo relanza.
     *
     * @throws {Error} siempre
     */
    function fail(err, context) {
        validationErrors.value = {};

        if (!err.response) {
            error.value = 'No se pudo contactar con el servidor. Revisa tu conexión.';
            throw err;
        }

        const { status, data } = err.response;

        if (status === 422) {
            validationErrors.value = data.errors ?? {};
            error.value = data.message ?? 'Revisa los datos introducidos.';
            throw err;
        }

        const messages = {
            401: 'Tu sesión ha caducado.',
            403: `No tienes permiso para ${context}.`,
            404: `No se encontró el ${singular}.`,
            429: 'Demasiadas peticiones. Espera un momento.',
        };

        error.value = messages[status] ?? data?.message ?? `No se pudo ${context}.`;
        throw err;
    }

    /**
     * El backend devuelve colección paginada o lista simple según el endpoint.
     */
    function unwrap(payload) {
        return Array.isArray(payload?.data) ? payload.data : (payload?.data ?? payload);
    }

    async function fetchAll(params = {}) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.get(`/${endpoint}`, { params });
            items.value = unwrap(data);
            meta.value = data.meta ?? null;

            return items.value;
        } catch (err) {
            return fail(err, `cargar los ${plural}`);
        } finally {
            loading.value = false;
        }
    }

    async function fetchById(id) {
        loading.value = true;
        error.value = null;

        try {
            const { data } = await apiClient.get(`/${endpoint}/${id}`);
            item.value = unwrap(data);

            return item.value;
        } catch (err) {
            return fail(err, `cargar el ${singular}`);
        } finally {
            loading.value = false;
        }
    }

    async function create(payload) {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            const { data } = await apiClient.post(`/${endpoint}`, payload);
            const created = unwrap(data);
            items.value = [created, ...items.value];

            return created;
        } catch (err) {
            return fail(err, `crear el ${singular}`);
        } finally {
            loading.value = false;
        }
    }

    async function update(id, payload) {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            const { data } = await apiClient.put(`/${endpoint}/${id}`, payload);
            const updated = unwrap(data);

            // Comparación laxa a propósito: los llamantes pasan route.params.id,
            // que es una cadena, y los ids del servidor son números. Con === la
            // lista local nunca se refrescaba.
            const index = items.value.findIndex((row) => row.id == id);
            if (index !== -1) {
                items.value[index] = updated;
            }

            item.value = updated;

            return updated;
        } catch (err) {
            return fail(err, `actualizar el ${singular}`);
        } finally {
            loading.value = false;
        }
    }

    async function remove(id) {
        loading.value = true;
        error.value = null;

        try {
            await apiClient.delete(`/${endpoint}/${id}`);
            items.value = items.value.filter((row) => row.id != id);

            return true;
        } catch (err) {
            return fail(err, `eliminar el ${singular}`);
        } finally {
            loading.value = false;
        }
    }

    function clearErrors() {
        error.value = null;
        validationErrors.value = {};
    }

    /**
     * Primer mensaje de error de un campo, para pintarlo junto al input.
     */
    function fieldError(field) {
        return validationErrors.value[field]?.[0] ?? null;
    }

    return {
        items,
        item,
        meta,
        loading,
        error,
        validationErrors,
        fetchAll,
        fetchById,
        create,
        update,
        remove,
        clearErrors,
        fieldError,
    };
}
