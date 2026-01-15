<template>
  <div class="w-full">
    <!-- =========================================================================
         ESTADO DE CARGA
         ========================================================================= -->
    <div v-if="loading" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
      <!-- Slot personalizable para el estado de carga -->
      <slot name="loading">
        <!-- Skeleton loader por defecto -->
        <div class="animate-pulse">
          <!-- Header del skeleton -->
          <div class="border-b border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-700">
            <div class="flex items-center space-x-4">
              <div v-for="col in columns" :key="col.key" class="h-4 flex-1 rounded bg-gray-300 dark:bg-gray-600"></div>
            </div>
          </div>
          <!-- Filas del skeleton -->
          <div v-for="i in 5" :key="i" class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
            <div class="flex items-center space-x-4">
              <div v-for="col in columns" :key="col.key" class="h-4 flex-1 rounded bg-gray-200 dark:bg-gray-600"></div>
            </div>
          </div>
        </div>
      </slot>
    </div>

    <!-- =========================================================================
         ESTADO DE ERROR
         ========================================================================= -->
    <div
      v-else-if="error"
      class="rounded-lg border border-red-200 bg-red-50 px-6 py-12 text-center dark:border-red-800 dark:bg-red-900/20"
    >
      <svg
        class="mx-auto h-12 w-12 text-red-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
      <h3 class="mt-4 text-lg font-medium text-red-900 dark:text-red-300">
        Error al cargar los datos
      </h3>
      <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ error }}</p>
    </div>

    <!-- =========================================================================
         ESTADO VACÍO
         ========================================================================= -->
    <div
      v-else-if="!items || items.length === 0"
      class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-800"
    >
      <!-- Slot personalizable para el estado vacío -->
      <slot name="empty">
        <svg
          class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
          />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
          No hay datos disponibles
        </h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          No se encontraron registros para mostrar.
        </p>
      </slot>
    </div>

    <!-- =========================================================================
         TABLA CON DATOS
         ========================================================================= -->
    <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <!-- ===================================================================
               HEADER DE LA TABLA
               =================================================================== -->
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th
                v-for="column in columns"
                :key="column.key"
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                :class="column.headerClass"
              >
                {{ column.label }}
              </th>
              <!-- Columna de acciones si existe el slot -->
              <th
                v-if="hasActionsSlot"
                scope="col"
                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
              >
                Acciones
              </th>
            </tr>
          </thead>

          <!-- ===================================================================
               BODY DE LA TABLA
               =================================================================== -->
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            <tr
              v-for="(item, index) in items"
              :key="getItemKey(item, index)"
              class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
              :class="rowClass"
            >
              <!-- Celdas de datos -->
              <td
                v-for="column in columns"
                :key="column.key"
                class="whitespace-nowrap px-6 py-4 text-sm"
                :class="column.cellClass"
              >
                <!-- Slot personalizado para cada celda si existe -->
                <slot
                  :name="`cell-${column.key}`"
                  :item="item"
                  :value="getNestedValue(item, column.key)"
                  :column="column"
                  :index="index"
                >
                  <!-- Renderizado por defecto: muestra el valor directamente -->
                  <span :class="getCellTextClass(item, column)">
                    {{ formatCellValue(getNestedValue(item, column.key), column) }}
                  </span>
                </slot>
              </td>

              <!-- Celda de acciones -->
              <td
                v-if="hasActionsSlot"
                class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium"
              >
                <slot name="actions" :item="item" :index="index">
                  <!-- Botones por defecto si no se proporciona el slot -->
                  <div class="flex items-center justify-end space-x-2">
                    <button
                      @click="handleView(item)"
                      class="text-blue-600 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:text-blue-400 dark:hover:text-blue-300 dark:focus:ring-offset-gray-800"
                      title="Ver detalles"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                      </svg>
                    </button>
                    <button
                      @click="handleEdit(item)"
                      class="text-green-600 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:text-green-400 dark:hover:text-green-300 dark:focus:ring-offset-gray-800"
                      title="Editar"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                    </button>
                    <button
                      @click="handleDelete(item)"
                      class="text-red-600 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:text-red-400 dark:hover:text-red-300 dark:focus:ring-offset-gray-800"
                      title="Eliminar"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                  </div>
                </slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue';

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * DATATABLE COMPONENT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Componente de tabla de datos reutilizable y altamente personalizable.
 *
 * CARACTERÍSTICAS:
 * - Completamente agnóstico a los datos
 * - Personalizable mediante slots
 * - Manejo de estados (loading, error, empty, data)
 * - Soporte para celdas personalizadas
 * - Acciones configurables por fila
 * - Estilizado con Tailwind CSS
 *
 * USO BÁSICO:
 * ```vue
 * <DataTable
 *   :columns="[
 *     { key: 'name', label: 'Nombre' },
 *     { key: 'email', label: 'Email' }
 *   ]"
 *   :items="users"
 *   :loading="isLoading"
 *   :error="errorMessage"
 *   @view-item="handleView"
 *   @edit-item="handleEdit"
 *   @delete-item="handleDelete"
 * >
 *   <template #cell-status="{ value }">
 *     <span :class="statusClass">{{ value }}</span>
 *   </template>
 * </DataTable>
 * ```
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

// ============================================================================
// PROPS
// ============================================================================

const props = defineProps({
  /**
   * Columnas de la tabla
   * Cada columna debe tener { key: string, label: string }
   * Opcionales: headerClass, cellClass, format (función de formateo)
   */
  columns: {
    type: Array,
    required: true,
    validator: (columns) => {
      return columns.every(col => col.key && col.label);
    }
  },

  /**
   * Array de items a mostrar en la tabla
   * Proveniente de la API o cualquier fuente de datos
   */
  items: {
    type: Array,
    default: () => []
  },

  /**
   * Estado de carga
   * Cuando es true, muestra el skeleton loader
   */
  loading: {
    type: Boolean,
    default: false
  },

  /**
   * Mensaje de error
   * Si tiene valor, muestra el estado de error
   */
  error: {
    type: String,
    default: null
  },

  /**
   * Clave única para cada item
   * Por defecto usa 'id', pero se puede cambiar
   */
  itemKey: {
    type: String,
    default: 'id'
  },

  /**
   * Clase CSS adicional para las filas
   */
  rowClass: {
    type: String,
    default: ''
  }
});

// ============================================================================
// EMITS
// ============================================================================

const emit = defineEmits([
  /**
   * Se emite cuando se hace clic en el botón "Ver"
   * @param {Object} item - El item de la fila
   */
  'view-item',

  /**
   * Se emite cuando se hace clic en el botón "Editar"
   * @param {Object} item - El item de la fila
   */
  'edit-item',

  /**
   * Se emite cuando se hace clic en el botón "Eliminar"
   * @param {Object} item - El item de la fila
   */
  'delete-item'
]);

// ============================================================================
// SLOTS
// ============================================================================

const slots = useSlots();

/**
 * Verifica si existe el slot de acciones
 */
const hasActionsSlot = computed(() => {
  return !!slots.actions || emit['view-item'] || emit['edit-item'] || emit['delete-item'];
});

// ============================================================================
// MÉTODOS
// ============================================================================

/**
 * Obtiene la clave única de un item
 * @param {Object} item - El item
 * @param {Number} index - El índice como fallback
 * @returns {String|Number} La clave única
 */
const getItemKey = (item, index) => {
  return item[props.itemKey] || index;
};

/**
 * Obtiene un valor anidado de un objeto usando dot notation
 * Ejemplo: getNestedValue({ user: { name: 'John' } }, 'user.name') → 'John'
 *
 * @param {Object} obj - El objeto fuente
 * @param {String} path - La ruta en dot notation
 * @returns {*} El valor encontrado o undefined
 */
const getNestedValue = (obj, path) => {
  return path.split('.').reduce((current, key) => current?.[key], obj);
};

/**
 * Formatea el valor de una celda
 * Si la columna tiene una función format, la usa
 *
 * @param {*} value - El valor a formatear
 * @param {Object} column - La definición de la columna
 * @returns {*} El valor formateado
 */
const formatCellValue = (value, column) => {
  // Si la columna tiene una función de formateo personalizada, usarla
  if (column.format && typeof column.format === 'function') {
    return column.format(value);
  }

  // Si el valor es null o undefined, mostrar guión
  if (value === null || value === undefined) {
    return '—';
  }

  // Si es un booleano, convertir a texto
  if (typeof value === 'boolean') {
    return value ? 'Sí' : 'No';
  }

  // Si es un array, unir con comas
  if (Array.isArray(value)) {
    return value.join(', ');
  }

  // Si es un objeto, intentar convertir a JSON
  if (typeof value === 'object') {
    return JSON.stringify(value);
  }

  // Valor por defecto
  return value;
};

/**
 * Obtiene las clases CSS para el texto de una celda
 * Puede ser sobrescrito por la prop cellClass de la columna
 *
 * @param {Object} item - El item de la fila
 * @param {Object} column - La definición de la columna
 * @returns {String} Las clases CSS
 */
const getCellTextClass = (item, column) => {
  // Si la columna define una clase específica, usarla
  if (column.cellClass) {
    return column.cellClass;
  }

  // Clase por defecto
  return 'text-gray-900 dark:text-gray-100';
};

// ============================================================================
// HANDLERS DE ACCIONES
// ============================================================================

/**
 * Maneja el clic en el botón "Ver"
 * @param {Object} item - El item de la fila
 */
const handleView = (item) => {
  emit('view-item', item);
};

/**
 * Maneja el clic en el botón "Editar"
 * @param {Object} item - El item de la fila
 */
const handleEdit = (item) => {
  emit('edit-item', item);
};

/**
 * Maneja el clic en el botón "Eliminar"
 * @param {Object} item - El item de la fila
 */
const handleDelete = (item) => {
  emit('delete-item', item);
};
</script>

<style scoped>
/**
 * Estilos adicionales si son necesarios
 * Preferiblemente usa Tailwind CSS
 */

/* Animación suave para el hover de las filas */
tbody tr {
  transition: background-color 150ms ease-in-out;
}

/* Asegurar que los iconos de acciones tengan el tamaño correcto */
button svg {
  display: block;
}
</style>
