import { useResource } from './useResource';

/**
 * CRUD de laboratorios.
 *
 * Toda la mecánica vive en useResource: este fichero solo fija el endpoint y
 * los textos. Antes eran 367 líneas que repetían las mismas diez funciones que
 * useEquipment y useSoftware, con divergencias que causaban errores reales.
 */
export function useLabs() {
    const resource = useResource('labs', {
        singular: 'laboratorio',
        plural: 'laboratorios',
    });

    return {
        ...resource,
        labs: resource.items,
        lab: resource.item,
        fetchLabs: resource.fetchAll,
        fetchLabById: resource.fetchById,
        createLab: resource.create,
        updateLab: resource.update,
        deleteLab: resource.remove,
    };
}
