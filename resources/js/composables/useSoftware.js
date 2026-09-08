import { useResource } from './useResource';

/**
 * CRUD de software. Ver useResource para el contrato: todo error se lanza.
 */
export function useSoftware() {
    const resource = useResource('software', {
        singular: 'software',
        plural: 'software',
    });

    return {
        ...resource,
        software: resource.items,
        softwareItem: resource.item,
        fetchSoftware: resource.fetchAll,
        fetchSoftwareById: resource.fetchById,
        createSoftware: resource.create,
        updateSoftware: resource.update,
        deleteSoftware: resource.remove,
    };
}
