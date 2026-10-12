# Changelog

Cambios de Lab-Reserva, del más reciente al más antiguo. Las fechas son las de
los commits; el detalle de cada entrada está en el historial de git.

## 2026-10-12 · Registro de actividades y menú Seguridad

- **Registro de actividades inmutable** (`activity_logs`): quién hizo qué,
  cuándo, desde qué IP y navegador, con el cambio antes/después de cada campo.
- Captura automática por eventos de modelo (`ActivityObserver`) sobre
  laboratorios, equipos, software, reservas, usuarios, roles, permisos,
  asignaciones, excepciones, cierres, periodos e incidencias.
- Eventos explícitos donde no hay eventos de modelo: inicio y cierre de sesión,
  intentos fallidos, permisos de un rol, software de un equipo, horario de
  apertura, plano, cierres automáticos del programador y exportación de reportes.
- Contraseñas, `remember_token` y códigos de asistencia se registran como `[oculto]`.
- Solo lectura: única ruta `GET /api/v1/activity-logs`; el modelo rechaza
  ediciones y borrados. Solo para el rol base `admin`, no se concede por RBAC.
  Sin purga: se conserva para siempre.
- Nuevo menú **Seguridad** (Roles, Permisos del Sistema, Reglas de Grupo y
  Registro de actividad); Administración conserva Usuarios, Reportes,
  Incidencias, Cierres y Periodos. Botón "Ver actividad" en el detalle de usuario.
- 13 pruebas de feature y 1 spec de Vitest nuevas.

## 2026-10-08 · Actualización de dependencias

- `league/commonmark` 2.10.3 (DoS en tablas GFM y bypass de `DisallowedRawHtml`).
- Laravel 12.69.3, Symfony 7.4 de parche, Monolog 3.12.1, Carbon 3.14.2.
- Desarrollo: Larastan 3.13, PHPStan 2.3, PHPUnit 11.5.57, Pint 1.32.1.
- npm: Vue 3.5.43, Vite 7.3.7, Vitest 5.0.3; `shell-quote` forzado a ^1.11.0.
- Sin arreglo aplicable (solo build de Tailwind 3): `braces` 3.0.3 y
  `postcss-selector-parser` 6.

## 2026-09-24 · Invitación de usuarios, Docker y licencia (PR #2)

- Alta por invitación con contraseña temporal y cambio obligatorio
  (`lab:invite-user`, middleware `password.changed`).
- Despliegue en contenedores Docker detrás de proxy inverso bajo un subdirectorio.
- Licencia propietaria de solo lectura y guía de instalación.

## 2026-09-10 · Roadmap fases 1 a 5

- Fase 1: reserva de laboratorio completo, flujo de aprobación y cuotas por rol.
- Fase 2: horario de apertura, cierres, periodos académicos y notificaciones.
- Fase 3: check-in con código, inasistencias con bloqueo y series recurrentes.
- Fase 4: gestión de usuarios y reportes de uso con exportación CSV.
- Fase 5: incidencias, software por equipo y mapa del laboratorio.
- Transversal: perfil editable, recuperación de contraseña y menú móvil.

## 2026-09-08 · Saneamiento, fases 1 a 9 (PR #1)

- Modelos renombrados a PascalCase singular y correcciones de bajo riesgo.
- Arnés de pruebas sobre MySQL con factories y pipeline de CI real.
- Seguridad: cierre de la escalada de privilegios y contrato de errores de la API.
- Reservas: solapamiento unificado, race condition corregida y ciclo de vida fijo.
- RBAC cableado al control de acceso por permisos efectivos.
- Rendimiento: N+1 eliminado e índices ajustados.
- Frontend: sesión, contrato de datos, composables unificados y componentes base.
- CI con análisis estático, pruebas de frontend y cobertura.

## 2026-07-03 · Auditoría y limpieza

- Código muerto y scripts temporales retirados, documentación reorganizada.

## 2026-06-01 · Roles y permisos

- Primera versión del sistema de roles y permisos y mejoras en reservas.

## 2026-01-15 · Tema oscuro

- Tema claro/oscuro y estilos de componentes actualizados.

## 2025-10 · Primera versión

- CRUD de laboratorios, equipos y software, sistema de reservas con estados y
  dashboard con datos reales.

---

## Notas de la plantilla laravel/laravel

### [Unreleased](https://github.com/laravel/laravel/compare/v12.5.0...12.x)

### [v12.5.0](https://github.com/laravel/laravel/compare/v12.4.0...v12.5.0) - 2025-09-30

* [12.x] Fix type casting for environment variables in config files by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6670
* Fix CVEs affecting vite by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/laravel/pull/6672
* Update .editorconfig to target compose.yaml by [@fredikaputra](https://github.com/fredikaputra) in https://github.com/laravel/laravel/pull/6679
* Add pre-package-uninstall script to composer.json by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/laravel/pull/6681

### [v12.4.0](https://github.com/laravel/laravel/compare/v12.3.1...v12.4.0) - 2025-08-29

* [12.x] Add default Redis retry configuration by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/laravel/pull/6666

### [v12.3.1](https://github.com/laravel/laravel/compare/v12.3.0...v12.3.1) - 2025-08-21

* [12.x] Bump Pint version by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6653
* [12.x] Making sure all related processed are closed when terminating the currently command by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6654
* [12.x] Use application name from configuration by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6655
* Bring back postAutoloadDump script by [@jasonvarga](https://github.com/jasonvarga) in https://github.com/laravel/laravel/pull/6662

### [v12.3.0](https://github.com/laravel/laravel/compare/v12.2.0...v12.3.0) - 2025-08-03

* Fix Critical Security Vulnerability in form-data Dependency by [@izzygld](https://github.com/izzygld) in https://github.com/laravel/laravel/pull/6645
* Revert "fix" by [@RobertBoes](https://github.com/RobertBoes) in https://github.com/laravel/laravel/pull/6646
* Change composer post-autoload-dump script to Artisan command by [@lmjhs](https://github.com/lmjhs) in https://github.com/laravel/laravel/pull/6647

### [v12.2.0](https://github.com/laravel/laravel/compare/v12.1.0...v12.2.0) - 2025-07-11

* Add Vite 7 support by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6639

### [v12.1.0](https://github.com/laravel/laravel/compare/v12.0.11...v12.1.0) - 2025-07-03

* [12.x] Disable nightwatch in testing by [@laserhybiz](https://github.com/laserhybiz) in https://github.com/laravel/laravel/pull/6632
* [12.x] Reorder environment variables in phpunit.xml for logical grouping by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6634
* Change to hyphenate prefixes and cookie names by [@u01jmg3](https://github.com/u01jmg3) in https://github.com/laravel/laravel/pull/6636
* [12.x] Fix type casting for environment variables in config files by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6637

### [v12.0.11](https://github.com/laravel/laravel/compare/v12.0.10...v12.0.11) - 2025-06-10

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.0.10...v12.0.11

### [v12.0.10](https://github.com/laravel/laravel/compare/v12.0.9...v12.0.10) - 2025-06-09

* fix alphabetical order by [@Khuthaily](https://github.com/Khuthaily) in https://github.com/laravel/laravel/pull/6627
* [12.x] Reduce redundancy and keeps the .gitignore file cleaner by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6629
* [12.x] Fix: Add void return type to satisfy Rector analysis by [@Aluisio-Pires](https://github.com/Aluisio-Pires) in https://github.com/laravel/laravel/pull/6628

### [v12.0.9](https://github.com/laravel/laravel/compare/v12.0.8...v12.0.9) - 2025-05-26

* [12.x] Remove apc by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6611
* [12.x] Add JSON Schema to package.json by [@martinbean](https://github.com/martinbean) in https://github.com/laravel/laravel/pull/6613
* Minor language update by [@woganmay](https://github.com/woganmay) in https://github.com/laravel/laravel/pull/6615
* Enhance .gitignore to exclude common OS and log files by [@mohammadRezaei1380](https://github.com/mohammadRezaei1380) in https://github.com/laravel/laravel/pull/6619

### [v12.0.8](https://github.com/laravel/laravel/compare/v12.0.7...v12.0.8) - 2025-05-12

* [12.x] Clean up URL formatting in README by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6601

### [v12.0.7](https://github.com/laravel/laravel/compare/v12.0.6...v12.0.7) - 2025-04-15

* Add `composer run test` command by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/laravel/pull/6598
* Partner Directory Changes in ReadME by [@joshcirre](https://github.com/joshcirre) in https://github.com/laravel/laravel/pull/6599

### [v12.0.6](https://github.com/laravel/laravel/compare/v12.0.5...v12.0.6) - 2025-04-08

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.0.5...v12.0.6

### [v12.0.5](https://github.com/laravel/laravel/compare/v12.0.4...v12.0.5) - 2025-04-02

* [12.x] Update `config/mail.php` to match the latest core configuration by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6594

### [v12.0.4](https://github.com/laravel/laravel/compare/v12.0.3...v12.0.4) - 2025-03-31

* Bump vite from 6.0.11 to 6.2.3 - Vulnerability patch by [@abdel-aouby](https://github.com/abdel-aouby) in https://github.com/laravel/laravel/pull/6586
* Bump vite from 6.2.3 to 6.2.4 by [@thinkverse](https://github.com/thinkverse) in https://github.com/laravel/laravel/pull/6590

### [v12.0.3](https://github.com/laravel/laravel/compare/v12.0.2...v12.0.3) - 2025-03-17

* Remove reverted change from CHANGELOG.md by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/laravel/pull/6565
* Improves clarity in app.css file by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6569
* [12.x] Refactor: Structural improvement for clarity by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6574
* Bump axios from 1.7.9 to 1.8.2 - Vulnerability patch by [@abdel-aouby](https://github.com/abdel-aouby) in https://github.com/laravel/laravel/pull/6572
* [12.x] Remove Unnecessarily [@source](https://github.com/source) by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6584

### [v12.0.2](https://github.com/laravel/laravel/compare/v12.0.1...v12.0.2) - 2025-03-04

* Make the github test action run out of the box independent of the choice of testing framework by [@ndeblauw](https://github.com/ndeblauw) in https://github.com/laravel/laravel/pull/6555

### [v12.0.1](https://github.com/laravel/laravel/compare/v12.0.0...v12.0.1) - 2025-02-24

* [12.x] prefer stable stability by [@pataar](https://github.com/pataar) in https://github.com/laravel/laravel/pull/6548

### [v12.0.0 (2025-??-??)](https://github.com/laravel/laravel/compare/v11.0.2...v12.0.0)

Laravel 12 includes a variety of changes to the application skeleton. Please consult the diff to see what's new.
