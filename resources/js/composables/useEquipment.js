import { useResource } from './useResource';

/**
 * CRUD de equipos. Ver useResource para el contrato: todo error se lanza.
 *
 * La versión anterior devolvía `false` en deleteEquipment en vez de lanzar, y
 * por eso EquipmentIndexView mostraba un toast de éxito tras un borrado que el
 * servidor había rechazado.
 */
export function useEquipment() {
    const resource = useResource('equipment', {
        singular: 'equipo',
        plural: 'equipos',
    });

    return {
        ...resource,
        equipment: resource.items,
        equipmentItem: resource.item,
        fetchEquipment: resource.fetchAll,
        fetchEquipmentById: resource.fetchById,
        createEquipment: resource.create,
        updateEquipment: resource.update,
        deleteEquipment: resource.remove,
    };
}
