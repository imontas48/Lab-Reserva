# Plantilla de casos de prueba para JMeter

Usa esta plantilla para documentar y ejecutar casos de prueba de la aplicación con JMeter. Mantén cada caso de prueba en una sección separada.

## 1. Metadatos del plan de prueba
- Objetivo del plan: 
- Alcance (endpoints/funcionalidades cubiertas): 
- Ambiente (URL base, credenciales de prueba, datos semilla): 
- Versión de la app / commit: 
- Versión de JMeter y plugins: 
- Datos comunes reutilizables (CSV Data Set Config, variables de usuario): 

## 2. Caso de prueba

| Campo | Descripción / Valor |
| --- | --- |
| ID de Caso de Prueba | Identificador único del caso de prueba |
| Nombre de Caso de Prueba | Caso de uso o aspecto a probar |
| Descripción | Se probará la respuesta del sistema cuando se presenta X escenario |
| Precondiciones | Condiciones previas necesarias para ejecutar el caso |
| Relaciones Casos de Uso | include / extend / generalización (si aplica) |
| Pasos y condiciones de ejecución | Pasos detallados y datos de prueba (uno por línea) |
| Resultado Esperado | Resultado ideal según los pasos ejecutados |
| Estado del Caso de Prueba | Ejecutado: Exitoso / Fallido / Frenado; Pendiente de ejecución; En construcción |
| Resultado Obtenido | Completar tras la ejecución según reacción de la app |
| Errores detectados | Errores encontrados durante la ejecución |

## 3. Diseño en JMeter (checklist)
- Thread Group: número de hilos, ramp-up, loops.
- Config Elements: HTTP Request Defaults, CSV Data Set Config, User Defined Variables.
- Pre-Processors: HTTP URL Re-writing / BeanShell / JSR223.
- Samplers: HTTP Request; BeanShell Sampler si se requiere lógica custom.
- Post-Processors: JSON Extractor, Regular Expression Extractor, Debug PostProcessor.
- Assertions: Response Assertion (código 2xx, contenido), Duration Assertion, Size Assertion.
- Timers: Constant / Uniform Random (para pacing realista).
- Listeners: View Results Tree (solo en debug), Summary Report, Aggregate Report, Backend Listener (Influx/Prometheus si aplica).
- Datos de prueba: definir rutas a CSV y estructura de columnas.
- Limpieza: cerrar sesión / revertir datos si aplica.

## 4. Ejecución y evidencias
- Fecha y ejecutor: 
- Archivo .jmx usado: 
- Parámetros CLI: ejemplo `jmeter -n -t plan.jmx -l results.jtl -j jmeter.log -Jusers=10`
- Evidencias: capturas, logs (adjuntar rutas), `results.jtl`.
- Observaciones adicionales: 

## 5. Notas sobre BeanShell
- El BeanShell Sampler admite la interfaz Interruptible; puedes definir `interrupt()` en el script o en el archivo de inicio.
- Los elementos de prueba admiten las interfaces ThreadListener y TestListener; deben definirse en el archivo de inicialización (ver `BeanShellListeners.bshrc`).
- Referencia: https://jmeter.apache.org/usermanual/component_reference.html#BeanShell_Sampler

## 6. Ejemplo mínimo (HTTP Request)
- ID: TC-API-001
- Nombre: Obtener equipos disponibles
- Descripción: Verifica que `/api/equipment` responda 200 con lista no vacía.
- Precondiciones: Ambiente staging, token válido.
- Pasos: HTTP GET `/api/equipment` con header `Authorization: Bearer <token>`.
- Resultado esperado: Código 200, cuerpo JSON con `data` arreglo y `data[0].id` presente.
- Estado: Pendiente de ejecución.
- Resultado obtenido / Errores: (completar tras la corrida).

## 7. Casos prellenados para endpoints clave (copiar y ajustar)

### TC-API-LOGIN-001
- Nombre: Autenticación básica
- Descripción: Valida que el login devuelva token con credenciales válidas.
- Precondiciones: Usuario de prueba registrado (email/contraseña conocidos).
- Pasos: HTTP POST `/api/v1/login` con JSON `{ "email": "user@test.com", "password": "Secret123" }`.
- Resultado esperado: Código 200; cuerpo JSON con `token` no vacío y `user.email` coincidente.
- Estado: Pendiente de ejecución.
- Resultado obtenido / Errores: 

### TC-API-DASH-001
- Nombre: Métricas de dashboard
- Descripción: Verifica que las estadísticas se retornen para usuario autenticado.
- Precondiciones: Token válido obtenido en login.
- Pasos: HTTP GET `/api/v1/dashboard/stats` con header `Authorization: Bearer <token>`.
- Resultado esperado: Código 200; llaves `total_labs`, `total_equipment`, `total_reservations` presentes y numéricas.
- Estado: Pendiente de ejecución.
- Resultado obtenido / Errores: 

### TC-API-LABS-001
- Nombre: Listar laboratorios
- Descripción: Retorna listado de labs disponibles.
- Precondiciones: Token válido.
- Pasos: HTTP GET `/api/v1/labs` con header `Authorization`.
- Resultado esperado: Código 200; cuerpo JSON con arreglo `data` y elementos con `id`, `name`.
- Estado: Pendiente de ejecución.
- Resultado obtenido / Errores: 

### TC-API-EQP-001
- Nombre: Equipos por laboratorio
- Descripción: Lista equipos de un lab específico.
- Precondiciones: Token válido; existe lab con `id=1` (ajustar según datos).
- Pasos: HTTP GET `/api/v1/labs/1/equipment` con header `Authorization`.
- Resultado esperado: Código 200; `data` arreglo; cada item con `lab_id=1`.
- Estado: Pendiente de ejecución.
- Resultado obtenido / Errores: 

### TC-API-RES-001
- Nombre: Crear reserva
- Descripción: Crea una reserva para un equipo disponible.
- Precondiciones: Token válido; equipo `id=1` libre en franja; payload válido (fecha/hora).
- Pasos: HTTP POST `/api/v1/reservations` con JSON `{ "equipment_id": 1, "start_time": "2025-01-05T10:00:00Z", "end_time": "2025-01-05T11:00:00Z" }` y header `Authorization`.
- Resultado esperado: Código 201; cuerpo con `id` de reserva y `status` esperado (p.ej. `active`).
- Estado: Pendiente de ejecución.
- Resultado obtenido / Errores: 

### TC-API-RES-002
- Nombre: Listar mis reservas
- Descripción: Devuelve reservas del usuario autenticado.
- Precondiciones: Token válido; usuario tiene reservas.
- Pasos: HTTP GET `/api/v1/my-reservations` con header `Authorization`.
- Resultado esperado: Código 200; `data` arreglo; cada item con `user_id` del token.
- Estado: Pendiente de ejecución.
- Resultado obtenido / Errores: 

### TC-API-RES-003
- Nombre: Cancelar reserva
- Descripción: Cancela reserva existente del usuario.
- Precondiciones: Token válido; reserva `id` existente y cancelable.
- Pasos: HTTP PATCH `/api/v1/reservations/{id}/cancel` con header `Authorization`.
- Resultado esperado: Código 200; `status` pasa a `cancelled`; reserva ya no aparece como activa.
- Estado: Pendiente de ejecución.
- Resultado obtenido / Errores: 
