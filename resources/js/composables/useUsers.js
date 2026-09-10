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
        updateUser: resource.update,
        deleteUser: resource.remove,
        unblockUser,
    };
}
