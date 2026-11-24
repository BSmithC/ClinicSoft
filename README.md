# Gestor de Citas — documentación técnica

Sistema de gestión de citas médicas construido sobre el proyecto existente (no reescrito): **Laravel 10**, **Inertia 2**, **Vue 3**, **Vite 6** y **MySQL**. Autorización con `spatie/laravel-permission`.

La evolución hacia una plataforma de organización clínica tomó a eDoc solo como referencia conceptual (médicos, especialidades, sesiones con cupos, agenda, disponibilidad e historial). No se copió código de eDoc y no se cambió el stack.

## Stack y versiones verificadas

| Componente | Versión |
| --- | --- |
| Laravel | 10.50.4 |
| inertiajs/inertia-laravel | 2.0.28 |
| @inertiajs/vue3 | 2.3.29 |
| Vite | 6.4.6 |
| @vitejs/plugin-vue | 6.0.11 |
| apexcharts / vue3-apexcharts | 8.9.1 / 1.11.3 |

Se mantuvo Laravel 10 por decisión de proyecto; los avisos de `composer audit` correspondientes están listados individualmente en `config.policy.advisories.ignore-id` de `composer.json` (no se desactivó la política global).

## Arquitectura clínica (FASE 5–8)

- `users` + `doctors`: perfil clínico 1:1 mediante `doctors.user_id`. `appointments.doctor_id` y `medical_sessions.doctor_id` siguen siendo el `users.id` del médico; el backfill no reescribió ese campo.
- `specialties` y la tabla pivote `doctor_specialty`.
- `medical_sessions`: médico, especialidad, título, fecha, hora de inicio/fin, duración por cita, capacidad, estado y notas.
- `session_breaks` y `blocked_dates`: descansos y fechas bloqueadas que reducen los espacios reales.
- `appointments` extendido con `session_id`, `specialty_id`, `notes`, `cancelled_at`, `rescheduled_from_id` y `softDeletes`.
- `appointment_histories` y `activity_log`: trazabilidad de cambios de estado y acciones clínicas.
- `AvailabilityService` calcula los espacios reales a partir de sesiones, descansos, bloqueos, capacidad y citas no terminales. Vue solo renderiza ese resultado: no duplica reglas de negocio.

No existe portal de pacientes ni un tercer rol funcional. Los pacientes siguen siendo registros administrados por personal autorizado. El rol `patient` existe en BD pero no se usa para acceder al sistema.

## Instalación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configurar `DB_*` en `.env` y ejecutar:

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=ClinicalSeeder   # opcional: backfill de perfiles y catálogo clínico
npm run build
php artisan serve
```

El registro público está cerrado: se retiraron las rutas, la vista `Auth/Register.vue` y `RegisteredUserController`, y se eliminaron las referencias Ziggy al nombre `register`. Para crear el primer usuario con acceso clínico:

```bash
php artisan tinker
# User::factory()->create(['email' => 'medico@example.com'])->assignRole('doctor');
```

## Roles y permisos

- `admin`, `doctor`, `patient` (creados por `RoleSeeder`).
- Las rutas clínicas usan `role:admin|doctor`: `dashboard`, `agenda`, `availability`, `appointments`, `patients`, `doctors`, `specialties`, `sessions`, `blocked-dates`, `breaks`, `drugs`, `bills`, `medicalhistories`.
- `patient` solo puede gestionar su perfil.
- `RoleSeeder` asigna `doctor` a usuarios preexistentes sin rol.
- Policies registradas en `AuthServiceProvider`: `AppointmentPolicy`, `PatientPolicy`, `MedicalSessionPolicy`, `DoctorPolicy`, `SpecialtyPolicy`, `SchedulePolicy` y `ModulePolicy`.
- Alcance real:
  - `admin`: acceso completo.
  - `doctor`: solo sus citas, sesiones, agenda y horarios; ve pacientes con citas a su nombre; no crea/edita médicos ni especialidades.
  - `patient`: únicamente perfil.
- Todos los `show/edit/update/destroy` usan **route model binding**: es necesario para que `authorizeResource` pase el modelo al Gate; con `string $id` la policy no se resolvía y devolvía 403.

## Reglas de negocio de citas

- Estados (`App\Enums\AppointmentStatus`): `pending`, `confirmed`, `waiting`, `in_consultation`, `completed`, `cancelled`, `no_show`.
- Terminales: `completed`, `cancelled`, `no_show`. Se marcan `active=false` y dejan de ocupar cupo, validación e índice único.
- `completed` marca automáticamente `attended = true`.
- Transiciones centralizadas en `AppointmentStatusMachine`; una transición inválida devuelve error de validación, nunca se persiste.
- `DELETE appointments/{appointment}` hace **cancelación lógica** con historial (`status=cancelled`, `active=false`, `cancelled_at`), nunca borrado físico.
- Cancelar una sesión cancela las citas afectadas y deja historial; las sesiones con historial clínico no se borran físicamente.
- Horario configurable en `config/appointments.php`: lunes a viernes, 08:00–18:00, duración 15–240 min, sin solapes para el mismo médico.
- Validación centralizada en `AppointmentAvailabilityService` (legado) y `AvailabilityService` (sesiones reales).
- `drug_id` valida contra `drugs`, no contra `users`.

## Concurrencia e integridad en MySQL

- Todas las escrituras de citas pasan por `AppointmentBookingService` dentro de `DB::transaction`.
- `ConcurrencyLock::runMany()` adquiere `GET_LOCK` en orden determinista y libera con `RELEASE_LOCK`. Claves: `booking:{doctor}:{date}` y `patient:{patient}:{date}`; en reprogramaciones se bloquean también origen y destino.
- `changeStatus()` y `cancel()` vuelven a leer la cita con `lockForUpdate()` y revalidan el estado persistido, evitando que una instancia obsoleta autorice una transición inválida.
- Restricciones en BD: FK reales, `CHECK (status IN (...))`, índices de consulta e índices únicos de cupo por sesión y por médico/fecha/hora activa.
- En SQLite (suite de pruebas) los locks de MySQL se omiten, pero las transacciones y validaciones se ejecutan igual.

## Backend y frontend

- Form Requests validan cada operación; las reglas de negocio viven en servicios, no en Vue.
- Los controllers comparten con Inertia solo los props que las páginas consumen.
- Las páginas reescritas usan `<template>` primero y `<script>` con `export default` (Options API). El layout conserva su `<script setup>` original y los componentes compartidos usan el mismo patrón que los existentes.
- Se eliminaron bots y páginas muertas (`delete.vue`, `delente.vue`, stubs de `BillDetail`/`Prescription`), enlaces `href="#"` y `console.log` heredados.
- La ficha de historia médica estaba vacía y ahora muestra diagnóstico, constantes y relaciones reales cargadas por el controller.

## Seguridad aplicada

- `auth.user` compartido por Inertia solo con `id`, `name`, `email`, `roles`.
- Selectores de create/edit reducidos a `id`, `name`, `last_name` (o `name` en fármacos) para no exponer dirección, teléfono, seguro ni correo.
- `AddSecurityHeaders`: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Throttle: login (5 intentos por email+IP en `LoginRequest`), password reset / confirmación / cambio de contraseña (6/min).
- CORS restringido al `APP_URL` (no `*`).
- `APP_DEBUG=false` por defecto en `.env.example`; `SESSION_SECURE_COOKIE` como variable de entorno.
- No se aplica CSP estricta: el frontend actual depende de scripts generados por Vite y estilos de ApexCharts. Queda como mejora pendiente evaluada.

## Comandos de verificación

```bash
composer validate
composer check-platform-reqs
composer dump-autoload -o
composer audit --no-interaction
php artisan route:list --except-vendor
npm run build
php artisan test --no-ansi
php scripts/verify_mysql.php
```

Última ejecución verificada:

- **56 pruebas, 340 aserciones**, todas en verde.
- `npm run build` correcto (avisos no bloqueantes de Browserslist y chunks >500 kB por ApexCharts).
- `route:list`: 90 rutas, sin errores.
- `php scripts/verify_mysql.php`: verificación MySQL completa.
- `ZiggyRoutesTest` comprueba que ningún `route()` del frontend apunte a una ruta inexistente (error que produjo el fallback de `register` al cerrar el registro público).
- `composer audit`: 5 avisos de Laravel ignorados individualmente; quedan 27 avisos en 7 paquetes (`firebase/php-jwt`, `guzzlehttp/guzzle`, `guzzlehttp/psr7`, `phpseclib/phpseclib`, `phpunit/phpunit`, `psy/psysh`, `symfony/yaml`). Revisar antes de cada release; no se desactivó la política global.

### Verificación en MySQL real

Se creó la BD `gestordecitas` (utf8mb4 / utf8mb4_unicode_ci) y se ejecutaron las 14 migraciones iniciales más las 10 migraciones clínicas (`2026_11_24_000010`–`000019`) y los seeders sin errores. `php scripts/verify_mysql.php` comprueba:

- 6 FK en `appointments`: `doctor_id→users`, `patient_id→patients`, `drug_id→drugs`, `session_id→medical_sessions`, `specialty_id→specialties`, `rescheduled_from_id→appointments`.
- Índices `appointments_doctor_date_index`, `appointments_patient_id_index`, `appointments_status_index`, `appointments_date_index`, `appointments_doctor_date_start_active_unique`, `appointments_session_start_index`, `appointments_patient_date_status_index`, `appointments_session_slot_active_unique`.
- Roles `admin`, `doctor`, `patient`.
- Creación de citas, rechazo de solapes, huecos contiguos, liberación del hueco al cancelar y rechazo de FK inválida.
- La prueba se ejecuta dentro de una transacción que se revierte: compara el conteo inicial y final, por lo que no altera los datos demo.

Tras `ClinicalSeeder` se verificó el backfill: 201 perfiles `doctors`, 6 especialidades, 201 relaciones `doctor_specialty` y 100 citas conservando su `doctor_id` original (las citas sin sesión siguen siendo tratadas como legado).

## Despliegue en producción (HTTPS)

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`.
2. `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` (o `strict` si no hay integraciones cross-site).
3. Ejecutar `php artisan migrate --force` en MySQL y `php artisan db:seed --class=RoleSeeder` (y `ClinicalSeeder` solo si se desea el backfill inicial).
4. `npm run build` y publicar `public/build`.
5. Permisos de escritura en `storage/` y `bootstrap/cache/`.
6. Terminar TLS en el servidor/proxy y revisar `composer audit` antes de cada release.

## Limitaciones conocidas

- Las pruebas automatizadas usan SQLite en memoria; la verificación sobre MySQL se ejecuta por separado con `scripts/verify_mysql.php`. SQLite no permite añadir FK a tablas existentes, pero MySQL sí las tiene.
- No hay pruebas E2E en navegador; la verificación frontend es compilación + pruebas Inertia + `ZiggyRoutesTest`.
- ApexCharts genera chunks >500 kB y Browserslist tiene datos desactualizados (avisos de Vite, no errores).
- PHP 8.5 emite un aviso no bloqueante en `vendor/nunomaduro/collision` (`ReflectionMethod::setAccessible()` deprecated).
- `composer audit` todavía reporta 27 avisos en dependencias transitivas; la política de ignorados es individual y solo cubre Laravel 10.
- No se aplica CSP estricta por la dependencia actual de Vite/ApexCharts.
- El rol `patient` existe en BD pero no hay portal de pacientes.
