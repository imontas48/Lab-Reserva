import apiClient from '@/utils/api';
import { useResource } from './useResource';

/**
 * Gestión de usuarios (requiere users.*). Ver useResource para el contrato.
 */
export function useUsers() {
    const resource = useResource('users', {
        singular: 'usuario',
        plural: 'usuarios',
    });

    /**
     * Alta con contraseña temporal. No usa resource.create porque la
     * contraseña viaja fuera de "data" y solo en esta respuesta.
     *
     * @returns {Promise<{ user: object, temporaryPassword: string }>}
     */
    async function createUser(payload) {
        const { data } = await apiClient.post('/users', payload);
        resource.items.value = [data.data, ...resource.items.value];

        return { user: data.data, temporaryPassword: data.temporary_password };
    }

    async function unblockUser(id) {
        const { data } = await apiClient.patch(`/users/${id}/unblock`);
        resource.item.value = data.data;

        return data.data;
    }

    return {
        ...resource,
        users: resource.items,
        user: resource.item,
        fetchUsers: resource.fetchAll,
        fetchUserById: resource.fetchById,
        createUser,
        updateUser: resource.update,
        deleteUser: resource.remove,
        unblockUser,
    };
}
