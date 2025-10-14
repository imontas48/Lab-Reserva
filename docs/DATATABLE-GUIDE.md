# 📊 DataTable Component - Guía de Uso

## 🎯 Descripción

`DataTable.vue` es un componente de tabla de datos completamente reutilizable y altamente personalizable. Es la piedra angular para todas las vistas de listado en Lab-Reserva.

**Ubicación**: `resources/js/components/ui/DataTable.vue`

---

## ✨ Características

- ✅ **Agnóstico a los datos** - Funciona con cualquier tipo de datos
- ✅ **Altamente personalizable** - Slots para cada celda y estado
- ✅ **Cuatro estados manejados** - Loading, Error, Empty, Data
- ✅ **Acciones configurables** - View, Edit, Delete por defecto
- ✅ **Soporte para datos anidados** - Usa dot notation (ej. `user.name`)
- ✅ **Formateo personalizable** - Función `format` por columna
- ✅ **Responsive** - Scroll horizontal en pantallas pequeñas
- ✅ **Accesible** - Semántica HTML correcta
- ✅ **Estilizado profesional** - Tailwind CSS

---

## 📦 Props

### `columns` (Array, required)
Define las columnas de la tabla.

```javascript
columns: [
  {
    key: 'name',              // Clave del dato (soporta dot notation)
    label: 'Nombre',          // Texto del header
    headerClass: 'w-1/3',    // Clase CSS para el header (opcional)
    cellClass: 'font-medium', // Clase CSS para las celdas (opcional)
    format: (value) => value.toUpperCase() // Función de formateo (opcional)
  }
]
```

### `items` (Array, default: `[]`)
Array de datos a mostrar. Proveniente de la API.

```javascript
items: [
  { id: 1, name: 'Lab A', capacity: 30 },
  { id: 2, name: 'Lab B', capacity: 25 }
]
```

### `loading` (Boolean, default: `false`)
Indica si los datos están cargando. Muestra skeleton loader.

### `error` (String, default: `null`)
Mensaje de error si la carga falló. Muestra estado de error.

### `itemKey` (String, default: `'id'`)
Clave única para cada item (para el `:key` de Vue).

### `rowClass` (String, default: `''`)
Clase CSS adicional para las filas.

---

## 🎪 Slots

### Slots de Celda: `cell-[key]`

Para cada columna, puedes personalizar cómo se renderiza usando un slot.

```vue
<template #cell-status="{ item, value, column, index }">
  <span :class="{
    'bg-green-100 text-green-800': value === 'active',
    'bg-red-100 text-red-800': value === 'inactive'
  }" class="px-2 py-1 rounded-full text-xs">
    {{ value }}
  </span>
</template>
```

**Parámetros del slot:**
- `item` - El objeto completo de la fila
- `value` - El valor de la celda
- `column` - La definición de la columna
- `index` - El índice de la fila

### Slot de Acciones: `actions`

Personaliza los botones de acción.

```vue
<template #actions="{ item, index }">
  <div class="flex space-x-2">
    <button @click="$emit('view-item', item)">Ver</button>
    <button @click="$emit('edit-item', item)">Editar</button>
    <button 
      v-if="item.canDelete" 
      @click="$emit('delete-item', item)"
    >
      Eliminar
    </button>
  </div>
</template>
```

### Slot de Estado Vacío: `empty`

Personaliza el mensaje cuando no hay datos.

```vue
<template #empty>
  <div class="text-center py-12">
    <p class="text-gray-500">No se encontraron laboratorios</p>
    <button @click="createNew" class="mt-4 btn-primary">
      Crear Nuevo Laboratorio
    </button>
  </div>
</template>
```

### Slot de Estado de Carga: `loading`

Personaliza el skeleton loader.

```vue
<template #loading>
  <div class="p-12 text-center">
    <svg class="animate-spin h-8 w-8 mx-auto text-blue-600">
      <!-- Spinner SVG -->
    </svg>
    <p class="mt-2 text-gray-600">Cargando datos...</p>
  </div>
</template>
```

---

## 📡 Events

### `@view-item`
Se emite cuando se hace clic en "Ver".

```vue
<DataTable @view-item="handleView" />
```

```javascript
const handleView = (item) => {
  router.push(`/labs/${item.id}`);
};
```

### `@edit-item`
Se emite cuando se hace clic en "Editar".

```vue
<DataTable @edit-item="handleEdit" />
```

```javascript
const handleEdit = (item) => {
  router.push(`/labs/${item.id}/edit`);
};
```

### `@delete-item`
Se emite cuando se hace clic en "Eliminar".

```vue
<DataTable @delete-item="handleDelete" />
```

```javascript
const handleDelete = async (item) => {
  if (confirm('¿Estás seguro?')) {
    await deleteLab(item.id);
  }
};
```

---

## 💡 Ejemplos de Uso

### Ejemplo 1: Uso Básico

```vue
<template>
  <DataTable
    :columns="columns"
    :items="labs"
    :loading="isLoading"
    :error="errorMessage"
    @view-item="handleView"
    @edit-item="handleEdit"
    @delete-item="handleDelete"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '@/components/ui/DataTable.vue';
import apiClient from '@/utils/api';

const columns = ref([
  { key: 'name', label: 'Nombre' },
  { key: 'capacity', label: 'Capacidad' },
  { key: 'building', label: 'Edificio' }
]);

const labs = ref([]);
const isLoading = ref(false);
const errorMessage = ref(null);

const loadLabs = async () => {
  isLoading.value = true;
  errorMessage.value = null;
  
  try {
    const response = await apiClient.get('/labs');
    labs.value = response.data.data;
  } catch (error) {
    errorMessage.value = 'Error al cargar los laboratorios';
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  loadLabs();
});
</script>
```

### Ejemplo 2: Con Celdas Personalizadas

```vue
<template>
  <DataTable
    :columns="columns"
    :items="equipment"
    :loading="isLoading"
    @view-item="router.push(`/equipment/${$event.id}`)"
  >
    <!-- Badge de estado -->
    <template #cell-status="{ value }">
      <span :class="statusClasses[value]" class="px-2 py-1 rounded-full text-xs font-medium">
        {{ statusLabels[value] }}
      </span>
    </template>

    <!-- Software con lista -->
    <template #cell-software="{ value }">
      <div v-if="value && value.length" class="text-xs">
        <span 
          v-for="(soft, i) in value.slice(0, 3)" 
          :key="i"
          class="inline-block bg-blue-100 text-blue-800 px-2 py-0.5 rounded mr-1 mb-1"
        >
          {{ soft.name }}
        </span>
        <span v-if="value.length > 3" class="text-gray-500">
          +{{ value.length - 3 }} más
        </span>
      </div>
      <span v-else class="text-gray-400">—</span>
    </template>

    <!-- Fecha formateada -->
    <template #cell-lastMaintenance="{ value }">
      {{ value ? formatDate(value) : 'Nunca' }}
    </template>
  </DataTable>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import DataTable from '@/components/ui/DataTable.vue';

const router = useRouter();

const columns = ref([
  { key: 'name', label: 'Equipo' },
  { key: 'type', label: 'Tipo' },
  { key: 'status', label: 'Estado' },
  { key: 'software', label: 'Software' },
  { key: 'lastMaintenance', label: 'Último Mantenimiento' }
]);

const statusClasses = {
  available: 'bg-green-100 text-green-800',
  maintenance: 'bg-yellow-100 text-yellow-800',
  broken: 'bg-red-100 text-red-800'
};

const statusLabels = {
  available: 'Disponible',
  maintenance: 'Mantenimiento',
  broken: 'Averiado'
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES');
};
</script>
```

### Ejemplo 3: Con Datos Anidados

```vue
<template>
  <DataTable
    :columns="columns"
    :items="reservations"
    :loading="isLoading"
  >
    <!-- Usuario (datos anidados) -->
    <template #cell-user.name="{ item }">
      <div class="flex items-center">
        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-medium">
          {{ item.user.name.charAt(0).toUpperCase() }}
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-gray-900">{{ item.user.name }}</p>
          <p class="text-xs text-gray-500">{{ item.user.email }}</p>
        </div>
      </div>
    </template>

    <!-- Laboratorio (datos anidados) -->
    <template #cell-lab.name="{ item }">
      <div>
        <p class="text-sm font-medium text-gray-900">{{ item.lab.name }}</p>
        <p class="text-xs text-gray-500">{{ item.lab.building }}</p>
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import { ref } from 'vue';
import DataTable from '@/components/ui/DataTable.vue';

const columns = ref([
  { key: 'user.name', label: 'Usuario' },
  { key: 'lab.name', label: 'Laboratorio' },
  { key: 'startDate', label: 'Fecha Inicio' },
  { key: 'endDate', label: 'Fecha Fin' },
  { key: 'status', label: 'Estado' }
]);
</script>
```

### Ejemplo 4: Con Acciones Personalizadas

```vue
<template>
  <DataTable
    :columns="columns"
    :items="users"
    :loading="isLoading"
  >
    <template #actions="{ item }">
      <div class="flex items-center justify-end space-x-2">
        <!-- Botón Ver -->
        <button
          @click="viewUser(item)"
          class="p-1 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded"
          title="Ver perfil"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
        </button>

        <!-- Botón Editar -->
        <button
          @click="editUser(item)"
          class="p-1 text-green-600 hover:text-green-900 hover:bg-green-50 rounded"
          title="Editar usuario"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </button>

        <!-- Botón Suspender (solo si está activo) -->
        <button
          v-if="item.status === 'active'"
          @click="suspendUser(item)"
          class="p-1 text-orange-600 hover:text-orange-900 hover:bg-orange-50 rounded"
          title="Suspender usuario"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
          </svg>
        </button>

        <!-- Botón Eliminar (solo admin) -->
        <button
          v-if="authStore.isAdmin"
          @click="deleteUser(item)"
          class="p-1 text-red-600 hover:text-red-900 hover:bg-red-50 rounded"
          title="Eliminar usuario"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
        </button>
      </div>
    </template>
  </DataTable>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
</script>
```

### Ejemplo 5: Con Función de Formateo

```vue
<template>
  <DataTable
    :columns="columns"
    :items="transactions"
    :loading="isLoading"
  />
</template>

<script setup>
import { ref } from 'vue';
import DataTable from '@/components/ui/DataTable.vue';

const columns = ref([
  { 
    key: 'id', 
    label: 'ID',
    format: (value) => `#${value.toString().padStart(6, '0')}`
  },
  { 
    key: 'amount', 
    label: 'Monto',
    format: (value) => new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency: 'EUR'
    }).format(value)
  },
  { 
    key: 'date', 
    label: 'Fecha',
    format: (value) => new Date(value).toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  },
  {
    key: 'percentage',
    label: 'Porcentaje',
    format: (value) => `${value}%`
  }
]);
</script>
```

---

## 🎨 Personalización Visual

### Clases de Header Personalizadas

```javascript
columns: [
  { 
    key: 'name', 
    label: 'Nombre',
    headerClass: 'w-1/3 bg-blue-50' // Ancho y color de fondo
  },
  { 
    key: 'status', 
    label: 'Estado',
    headerClass: 'w-24 text-center' // Ancho fijo y centrado
  }
]
```

### Clases de Celda Personalizadas

```javascript
columns: [
  { 
    key: 'price', 
    label: 'Precio',
    cellClass: 'text-right font-mono text-green-600'
  },
  { 
    key: 'code', 
    label: 'Código',
    cellClass: 'font-mono text-xs bg-gray-50'
  }
]
```

---

## 🔍 Casos de Uso Comunes

### 1. Búsqueda en la Tabla

```vue
<template>
  <div>
    <input 
      v-model="searchQuery" 
      type="text" 
      placeholder="Buscar..."
      class="mb-4 px-4 py-2 border rounded"
    />
    
    <DataTable
      :columns="columns"
      :items="filteredItems"
      :loading="isLoading"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const searchQuery = ref('');
const items = ref([...]);

const filteredItems = computed(() => {
  if (!searchQuery.value) return items.value;
  
  return items.value.filter(item => 
    item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});
</script>
```

### 2. Paginación

```vue
<template>
  <div>
    <DataTable
      :columns="columns"
      :items="paginatedItems"
      :loading="isLoading"
    />
    
    <div class="mt-4 flex justify-between items-center">
      <button 
        @click="previousPage" 
        :disabled="currentPage === 1"
      >
        Anterior
      </button>
      <span>Página {{ currentPage }} de {{ totalPages }}</span>
      <button 
        @click="nextPage" 
        :disabled="currentPage === totalPages"
      >
        Siguiente
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const items = ref([...]);
const currentPage = ref(1);
const itemsPerPage = ref(10);

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return items.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(items.value.length / itemsPerPage.value);
});

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};
</script>
```

---

## ⚠️ Notas Importantes

1. **Dot Notation**: El componente soporta acceso a propiedades anidadas usando dot notation (ej. `user.name`, `lab.building.floor`).

2. **Performance**: Para grandes cantidades de datos (>1000 filas), considera implementar paginación del lado del servidor.

3. **Accesibilidad**: El componente usa semántica HTML correcta (`<table>`, `<thead>`, `<tbody>`, etc.) para máxima accesibilidad.

4. **Responsive**: En pantallas pequeñas, la tabla tiene scroll horizontal automático.

5. **Slots Dinámicos**: Puedes crear slots para cualquier columna usando el patrón `cell-[key]`.

---

## 🚀 Próximas Mejoras (Opcional)

- [ ] Ordenamiento por columnas (click en header)
- [ ] Selección múltiple con checkboxes
- [ ] Paginación integrada
- [ ] Exportación a CSV/Excel
- [ ] Filtros por columna
- [ ] Columnas redimensionables
- [ ] Sticky header en scroll

---

**Documentado por El Arquitecto - Lab-Reserva Team** 🚀
