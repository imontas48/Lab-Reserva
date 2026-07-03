#  Sistema de Notificaciones - Lab-Reserva

##  Descripción General

Sistema de notificaciones profesional implementado en toda la aplicación usando:
- **vue-toastification** para notificaciones toast
- **SweetAlert2** para diálogos de confirmación

---

##  Dependencias Instaladas

```bash
npm install vue-toastification@next
npm install sweetalert2
```

---

## ️ Configuración

### 1. Configuración Global (`resources/js/app.js`)

```javascript
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

const toastOptions = {
    position: 'top-right',
    timeout: 3000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: false,
    transition: 'Vue-Toastification__bounce',
    maxToasts: 5,
    newestOnTop: true
};

app.use(Toast, toastOptions);
```

### 2. Estilos Personalizados (`resources/css/app.css`)

Estilos personalizados para que combinen con el diseño de la aplicación (Tailwind CSS).

---

##  Composable `useToast`

### Ubicación
`resources/js/composables/useToast.js`

### Uso

```javascript
import { useToast } from '@/composables/useToast';

const toast = useToast();

// Notificaciones de éxito
toast.success('Operación exitosa');

// Notificaciones de error
toast.error('Algo salió mal');

// Notificaciones de advertencia
toast.warning('Ten cuidado');

// Notificaciones informativas
toast.info('Información importante');

// Limpiar todas las notificaciones
toast.clear();
```

### Métodos Disponibles

| Método | Descripción | Duración | Color |
|--------|-------------|----------|-------|
| `success(message, options)` | Notificación de éxito | 3s | Verde |
| `error(message, options)` | Notificación de error | 5s | Rojo |
| `warning(message, options)` | Notificación de advertencia | 4s | Naranja |
| `info(message, options)` | Notificación informativa | 3s | Azul |
| `clear()` | Limpiar todas las notificaciones | - | - |

---

##  Implementación en la Aplicación

###  Autenticación

#### LoginView (`resources/js/views/auth/LoginView.vue`)
```javascript
// Éxito
toast.success(`¡Bienvenido, ${authStore.userName}!`);

// Error
toast.error('Credenciales incorrectas. Por favor, verifica tus datos.');
```

#### RegisterView (`resources/js/views/auth/RegisterView.vue`)
```javascript
// Éxito
toast.success(`¡Registro exitoso! Bienvenido, ${authStore.userName}`);

// Error
toast.error('Error al registrar la cuenta. Por favor, intenta nuevamente.');
```

#### Logout (`resources/js/layouts/AppLayout.vue`)
```javascript
// Éxito
toast.info('Sesión cerrada exitosamente');
```

###  Laboratorios

#### LabsCreateEditView (`resources/js/views/labs/LabsCreateEditView.vue`)
```javascript
// Éxito al crear
toast.success(`Laboratorio "${result.name}" creado exitosamente`);

// Éxito al editar
toast.success(`Laboratorio "${result.name}" actualizado exitosamente`);

// Error de validación
toast.warning('Por favor, corrige los errores en el formulario');

// Error general
toast.error('Error al crear el laboratorio');
```

#### LabsIndexView (`resources/js/views/labs/LabsIndexView.vue`)
```javascript
// Confirmación con SweetAlert2
const result = await Swal.fire({
    title: '¿Estás seguro?',
    html: `Se eliminará el laboratorio <strong>"${lab.name}"</strong>.<br>Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusCancel: true
});

// Éxito al eliminar
toast.success(`Laboratorio "${lab.name}" eliminado exitosamente`);

// Error al eliminar
toast.error('Error al eliminar el laboratorio. Por favor, intenta nuevamente.');
```

---

##  Tipos de Notificaciones

### 1. Toast Notifications

**Características:**
-  Aparecen en la esquina superior derecha
-  Se auto-cierran después de 3-5 segundos
-  Tienen barra de progreso animada
-  Se pueden cerrar manualmente
-  Se pueden arrastrar
-  Máximo 5 notificaciones simultáneas

**Cuándo usar:**
- Confirmación de acciones exitosas
- Mensajes de error no críticos
- Información general
- Advertencias

### 2. SweetAlert2 Dialogs

**Características:**
-  Modal centrado en la pantalla
-  Bloquea la interacción con el resto de la página
-  Botones personalizados
-  Iconos intuitivos
-  Soporte para HTML en el contenido

**Cuándo usar:**
- Confirmación de acciones destructivas (eliminar)
- Decisiones importantes del usuario
- Mensajes de error críticos

---

##  Guía de Buenas Prácticas

### 1. **Mensajes Claros y Concisos**
```javascript
//  Bueno
toast.success('Laboratorio creado exitosamente');

//  Evitar
toast.success('Se ha creado correctamente el laboratorio en el sistema');
```

### 2. **Incluir Contexto Relevante**
```javascript
//  Bueno
toast.success(`Laboratorio "${lab.name}" eliminado exitosamente`);

//  Evitar
toast.success('Eliminado');
```

### 3. **Elegir el Tipo Correcto**
```javascript
//  Éxito - verde
toast.success('Operación completada');

//  Error - rojo
toast.error('No se pudo completar la operación');

//  Advertencia - naranja
toast.warning('Por favor, corrige los errores');

//  Información - azul
toast.info('Sesión cerrada');
```

### 4. **Usar SweetAlert2 para Confirmaciones Destructivas**
```javascript
//  Bueno
const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true
});

if (result.isConfirmed) {
    // Proceder con la acción
}
```

---

##  Extensión Futura

Para agregar notificaciones a nuevos módulos:

1. **Importar el composable:**
```javascript
import { useToast } from '@/composables/useToast';
```

2. **Inicializar:**
```javascript
const toast = useToast();
```

3. **Usar en tus métodos:**
```javascript
try {
    // Tu lógica
    toast.success('Operación exitosa');
} catch (error) {
    toast.error('Error en la operación');
}
```

---

##  Resumen de Implementación

| Módulo | Toast Success | Toast Error | Toast Warning | SweetAlert2 |
|--------|--------------|-------------|---------------|-------------|
| Login |  |  | - | - |
| Register |  |  | - | - |
| Logout |  (info) |  | - | - |
| Labs Create |  |  |  | - |
| Labs Edit |  |  |  | - |
| Labs Delete |  |  | - |  |

---

##  Siguiente Paso

Implementar el mismo sistema en los módulos de:
- **Equipos** (Equipment)
- **Software**
- **Reservaciones**

Siguiendo el mismo patrón establecido en el módulo de Laboratorios.
