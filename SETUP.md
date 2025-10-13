# Pasos Finales de Configuración

## ⚠️ REQUIERE: Extensión PHP fileinfo habilitada

Estos pasos solo pueden completarse después de habilitar la extensión `fileinfo` en PHP.

### Habilitar fileinfo en Laragon:

1. Abre Laragon
2. Click derecho en Laragon > PHP > Versión > php.ini
3. Busca la línea `;extension=fileinfo`
4. Quita el `;` para descomentarla: `extension=fileinfo`
5. Guarda el archivo
6. Reinicia Apache/Nginx desde Laragon

### Una vez habilitada fileinfo:

#### 1. Publicar archivos de Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Esto creará:
- `config/sanctum.php` - Configuración de Sanctum
- Migración de `personal_access_tokens` table

#### 2. Ejecutar migraciones de Sanctum

```bash
php artisan migrate
```

#### 3. Configurar Sanctum en api.php (Ya está por defecto en Laravel 12)

El middleware `auth:sanctum` debe estar aplicado a las rutas que requieren autenticación.

Ejemplo en `routes/api.php`:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Tus rutas protegidas aquí
    Route::apiResource('reservations', ReservationController::class);
});
```

#### 4. Configurar CORS (si es necesario)

Si el frontend está en un dominio diferente, configura CORS en `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'supports_credentials' => true,
```

#### 5. Probar la configuración

Crea una ruta de prueba protegida:

```bash
php artisan make:controller Api/TestController
```

En `routes/api.php`:

```php
Route::middleware('auth:sanctum')->get('/test', [TestController::class, 'index']);
```

## Base de Datos

### Configurar conexión MySQL en .env:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lab_reserva
DB_USERNAME=root
DB_PASSWORD=
```

### Crear la base de datos:

1. Abre HeidiSQL (incluido en Laragon)
2. Crea una nueva base de datos llamada `lab_reserva`
3. Ejecuta las migraciones:

```bash
php artisan migrate
```

## Probar la aplicación

### 1. Iniciar el servidor de desarrollo Laravel:

```bash
php artisan serve
```

O usa Laragon (la aplicación estará en `http://lab-reserva.test` si configuraste un virtual host)

### 2. Iniciar Vite para compilar assets:

```bash
npm run dev
```

### 3. Abrir en el navegador:

```
http://localhost:8000
```

O si usas Laragon:

```
http://lab-reserva.test
```

Deberías ver la pantalla de bienvenida de Lab-Reserva con:
- ✅ Laravel 12 LTS
- ✅ Vue 3 + Composition API
- ✅ Tailwind CSS
- ✅ Pinia State Management
- ✅ Axios HTTP Client

## Solución de Problemas

### Error: "Class 'finfo' not found"

- Asegúrate de haber habilitado `extension=fileinfo` en php.ini
- Reinicia Apache/Nginx
- Verifica con: `php -m | findstr fileinfo` (debe aparecer en la lista)

### Error: "SQLSTATE[HY000] [2002] No connection could be made"

- Verifica que MySQL esté corriendo en Laragon
- Verifica las credenciales en el archivo `.env`
- Asegúrate de haber creado la base de datos

### Assets no se cargan o errores de Vite

- Asegúrate de tener `npm run dev` corriendo
- Verifica que el archivo `vite.config.js` esté correctamente configurado
- Limpia la caché: `npm run build` y luego `npm run dev`

### Error 404 en rutas de Vue

- Verifica que la ruta catch-all esté en `routes/web.php`:
  ```php
  Route::get('/{any}', function () {
      return view('app');
  })->where('any', '.*');
  ```

## ¡Listo!

Una vez completados estos pasos, tendrás el proyecto Lab-Reserva completamente configurado y listo para comenzar el desarrollo de features.

El siguiente paso sería crear los modelos, migraciones y servicios para las entidades principales:
- Laboratorios
- Equipos
- Usuarios (ya incluido por defecto)
- Reservas
- Horarios
