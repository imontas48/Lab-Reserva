import { useResource } from './useResource';

/**
 * CRUD de periodos académicos. Ver useResource para el contrato.
 */
export function useAcademicPeriods() {
    const resource = useResource('academic-periods', {
        singular: 'periodo académico',
        plural: 'periodos académicos',
    });

    return {
        ...resource,
        periods: resource.items,
        period: resource.item,
        fetchPeriods: resource.fetchAll,
        createPeriod: resource.create,
        updatePeriod: resource.update,
        deletePeriod: resource.remove,
    };
}
