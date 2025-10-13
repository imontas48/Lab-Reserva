<?php

namespace App\Services;

/**
 * Servicio de ejemplo para demostrar la arquitectura
 * 
 * Los Services contienen TODA la lógica de negocio de la aplicación.
 * Los controladores SOLO deben invocar estos servicios.
 * 
 * Principios:
 * - Single Responsibility: Cada servicio maneja un dominio específico
 * - Inyección de dependencias: Recibe dependencias en el constructor
 * - Métodos descriptivos: Nombres que explican claramente la acción
 * - Validación de negocio: No solo validación de datos, sino reglas de negocio
 */
class ExampleService
{
    /**
     * Crea un nuevo recurso de ejemplo
     * 
     * @param array $data Datos validados del recurso
     * @return array Recurso creado
     */
    public function createResource(array $data): array
    {
        // Aquí iría la lógica de negocio
        // Por ejemplo:
        // - Validaciones de negocio adicionales
        // - Transformaciones de datos
        // - Cálculos complejos
        // - Interacción con múltiples modelos
        
        // Ejemplo simple
        return [
            'id' => 1,
            'name' => $data['name'] ?? 'Default Name',
            'created_at' => now(),
        ];
    }

    /**
     * Obtiene recursos con lógica de negocio aplicada
     * 
     * @return array Lista de recursos procesados
     */
    public function getResources(): array
    {
        // Lógica de negocio para obtener recursos
        // Puede incluir:
        // - Eager loading para evitar N+1
        // - Aplicación de scopes
        // - Transformaciones
        // - Cálculos agregados
        
        return [];
    }

    /**
     * Actualiza un recurso aplicando reglas de negocio
     * 
     * @param int $id ID del recurso
     * @param array $data Datos validados para actualizar
     * @return array Recurso actualizado
     */
    public function updateResource(int $id, array $data): array
    {
        // Lógica de negocio para actualización
        // - Verificar permisos específicos del negocio
        // - Validar transiciones de estado
        // - Registrar auditoría
        
        return [
            'id' => $id,
            'updated' => true,
        ];
    }

    /**
     * Elimina un recurso con validaciones de negocio
     * 
     * @param int $id ID del recurso
     * @return bool
     */
    public function deleteResource(int $id): bool
    {
        // Lógica de negocio para eliminación
        // - Verificar dependencias
        // - Soft delete si es necesario
        // - Limpiar recursos relacionados
        
        return true;
    }
}
