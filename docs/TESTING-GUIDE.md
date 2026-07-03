#  Guía de Pruebas - Frontend Lab-Reserva

##  Estado Actual

**Servidor de desarrollo activo**:  
- Vite corriendo en `http://localhost:5173/`
- Laravel app URL: `http://lab-reserva.test`

---

##  Instrucciones para Probar

### 1️⃣ **Preparación del Backend**

Asegúrate de que el backend de Laravel esté corriendo:

```bash
# En una terminal separada
php artisan serve
```

O si usas Laragon, asegúrate de que los servicios estén activos.

### 2️⃣ **Migrar la Base de Datos** (si no lo has hecho)

```bash
php artisan migrate:fresh --seed
```

Esto creará las tablas necesarias y poblará datos de prueba.

### 3️⃣ **Acceder a la Aplicación**

Abre tu navegador y visita:
```
http://lab-reserva.test
```

Deberías ver la página de login.

---

##  Flujo de Prueba de Autenticación

### **Escenario 1: Registro de Usuario**

1. Ve a `http://lab-reserva.test`
2. Haz clic en "Regístrate aquí"
3. Completa el formulario:
   - Nombre: `Juan Pérez`
   - Email: `juan@test.com`
   - Contraseña: `password123`
   - Confirmar Contraseña: `password123`
4. Haz clic en "Crear Cuenta"
5. **Resultado esperado**: Redirige al dashboard con datos del usuario

### **Escenario 2: Login**

1. Ve a `http://lab-reserva.test/login`
2. Ingresa credenciales:
   - Email: `juan@test.com`
   - Contraseña: `password123`
3. Marca "Recordarme" (opcional)
4. Haz clic en "Iniciar Sesión"
5. **Resultado esperado**: Redirige al dashboard

### **Escenario 3: Protección de Rutas**

1. **Sin autenticar**, intenta acceder a:
   - `http://lab-reserva.test/dashboard`
   - `http://lab-reserva.test/labs`
   - `http://lab-reserva.test/equipment`
2. **Resultado esperado**: Redirige a `/login` automáticamente

3. **Autenticado**, intenta acceder a:
   - `http://lab-reserva.test/login`
   - `http://lab-reserva.test/register`
4. **Resultado esperado**: Redirige a `/dashboard` automáticamente

### **Escenario 4: Navegación en la Aplicación**

1. Login con credenciales válidas
2. Navega por el menú:
   - Dashboard
   - Laboratorios
   - Equipos
   - Mis Reservas
3. **Resultado esperado**: Navegación fluida, URLs cambian correctamente

### **Escenario 5: Logout**

1. Estando autenticado, haz clic en "Cerrar Sesión"
2. **Resultado esperado**: 
   - Redirige a `/login`
   - Si intentas volver a `/dashboard`, redirige a login

### **Escenario 6: Persistencia de Sesión**

1. Login con credenciales válidas
2. Recarga la página (F5)
3. **Resultado esperado**: Sigues autenticado, no te redirige a login

---

##  Pruebas de la API (desde la consola del navegador)

Abre las **DevTools** (F12) y ve a la pestaña **Console**.

### **1. Verificar configuración de Axios**

```javascript
// Debería estar disponible globalmente
console.log(window.axios);
```

### **2. Probar obtención de CSRF Token**

```javascript
// Esto debería ejecutarse automáticamente en el interceptor
fetch('http://lab-reserva.test/sanctum/csrf-cookie', {
    credentials: 'include'
}).then(() => console.log('CSRF token obtenido'));
```

### **3. Ver cookies de sesión**

En DevTools, ve a:
- **Application** → **Cookies** → `http://lab-reserva.test`

Deberías ver:
- `XSRF-TOKEN` - Token CSRF
- `laravel_session` - Sesión de Laravel

---

##  Troubleshooting

### **Problema 1: Error 419 (CSRF Token Mismatch)**

**Síntomas**:
- Al hacer login, recibes error 419

**Solución**:
1. Verifica que en `.env` tengas:
   ```env
   SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:5173,127.0.0.1:3000,127.0.0.1:5173,lab-reserva.test
   ```

2. Limpia la caché de Laravel:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. Limpia las cookies del navegador (Application → Clear site data)

### **Problema 2: Error 401 (Unauthorized)**

**Síntomas**:
- Después de login exitoso, al navegar recibes 401

**Solución**:
1. Verifica que el middleware de Sanctum esté configurado en `app/Http/Kernel.php`:
   ```php
   'api' => [
       \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
   ],
   ```

2. Verifica que en `config/cors.php`:
   ```php
   'supports_credentials' => true,
   ```

### **Problema 3: Rutas no funcionan (404)**

**Síntomas**:
- Al navegar a una ruta, obtienes 404 de Laravel

**Solución**:
Verifica que en `routes/web.php` tengas:
```php
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
```

### **Problema 4: Vue no se carga**

**Síntomas**:
- Página en blanco
- Error en consola: "Uncaught..."

**Solución**:
1. Asegúrate de que Vite esté corriendo:
   ```bash
   npm run dev
   ```

2. Verifica que en `app.blade.php` tengas:
   ```blade
   @vite(['resources/css/app.css', 'resources/js/app.js'])
   ```

### **Problema 5: Error de CORS**

**Síntomas**:
- Error en consola: "blocked by CORS policy"

**Solución**:
1. Verifica `config/cors.php`:
   ```php
   'paths' => ['api/*', 'sanctum/csrf-cookie'],
   'allowed_origins' => ['http://lab-reserva.test', 'http://localhost:5173'],
   'supports_credentials' => true,
   ```

2. Reinicia el servidor de Laravel

---

##  Verificación de Funcionalidad

###  Checklist de Pruebas

- [ ] **Página de Login carga correctamente**
- [ ] **Registro de usuario funciona**
- [ ] **Login con credenciales válidas funciona**
- [ ] **Login con credenciales inválidas muestra error**
- [ ] **Errores de validación se muestran correctamente**
- [ ] **Dashboard carga después de login**
- [ ] **Navegación entre rutas funciona**
- [ ] **Rutas protegidas redirigen a login si no autenticado**
- [ ] **Rutas de guest redirigen a dashboard si autenticado**
- [ ] **Logout funciona correctamente**
- [ ] **Sesión persiste al recargar página**
- [ ] **Token CSRF se obtiene automáticamente**
- [ ] **Interceptores de Axios funcionan correctamente**
- [ ] **Navbar muestra nombre de usuario**
- [ ] **Página 404 funciona para rutas inexistentes**

---

##  Inspección en DevTools

### **Network Tab**

Al hacer login, deberías ver estas peticiones en orden:

1. **GET** `/sanctum/csrf-cookie` - Obtener token CSRF
   - Status: 204 No Content
   - Headers debe incluir `Set-Cookie: XSRF-TOKEN`

2. **POST** `/api/v1/login` - Autenticar usuario
   - Status: 200 OK (o 204)
   - Headers debe incluir `Set-Cookie: laravel_session`

3. **GET** `/api/v1/user` - Obtener datos del usuario
   - Status: 200 OK
   - Response debe tener datos del usuario

### **Console Tab**

Deberías ver logs de:
```
 Obteniendo token CSRF de Sanctum...
 Token CSRF obtenido correctamente
 Iniciando sesión...
 Login exitoso, obteniendo datos del usuario...
 Usuario autenticado: {name: "Juan Pérez", ...}
 Usuario autenticado completamente
 Navegación: login → dashboard
```

---

##  Próximas Pruebas (Cuando se implementen las vistas)

### **Laboratorios**
- [ ] Listar laboratorios
- [ ] Ver detalle de laboratorio
- [ ] Crear laboratorio (solo admin)
- [ ] Editar laboratorio (solo admin)
- [ ] Eliminar laboratorio (solo admin)

### **Equipos**
- [ ] Listar equipos
- [ ] Ver detalle de equipo
- [ ] Crear equipo (solo admin)
- [ ] Editar equipo (solo admin)
- [ ] Eliminar equipo (solo admin)

### **Reservas**
- [ ] Listar mis reservas
- [ ] Crear nueva reserva
- [ ] Ver detalle de reserva
- [ ] Cancelar reserva

---

##  Notas Importantes

### **1. Dominio Local**

Si usas Laragon, el dominio `lab-reserva.test` debería estar configurado automáticamente.

Si no funciona, verifica:
```
C:\Windows\System32\drivers\etc\hosts
```

Debería tener:
```
127.0.0.1 lab-reserva.test
```

### **2. Puerto de Vite**

Vite usa el puerto `5173` por defecto. Si está ocupado, cambiará automáticamente.
El puerto real se muestra al ejecutar `npm run dev`.

### **3. Hot Module Replacement (HMR)**

Con Vite corriendo (`npm run dev`), los cambios en archivos `.vue` se reflejan
automáticamente sin recargar la página completa.

---

##  Comandos Útiles Durante el Desarrollo

### **Laravel**
```bash
php artisan serve           # Iniciar servidor
php artisan migrate:fresh   # Resetear DB
php artisan tinker          # Consola interactiva
php artisan route:list      # Ver rutas
```

### **Frontend**
```bash
npm run dev                 # Servidor de desarrollo
npm run build               # Compilar para producción
```

### **Depuración**
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

##  Características Implementadas para Probar

### **1. Auto-renovación de CSRF Token**
- El token se obtiene automáticamente antes de la primera petición POST
- Si expira (error 419), se renueva y se reintenta la petición

### **2. Manejo de Errores**
- Error 401 → Redirige a login automáticamente
- Error 422 → Muestra errores de validación en el formulario
- Error 419 → Renueva token y reintenta

### **3. Validación en Tiempo Real**
- Los errores desaparecen al escribir en el campo

### **4. Estados de Carga**
- Botones muestran "Iniciando sesión..." durante el proceso
- Se deshabilitan para evitar múltiples envíos

### **5. Persistencia de Sesión**
- Al recargar, verifica si hay sesión activa
- Restaura datos del usuario automáticamente

---

**¡Listo para probar!** 

Si encuentras algún problema, consulta la sección de **Troubleshooting** o revisa los logs en la consola del navegador y en `storage/logs/laravel.log`.
