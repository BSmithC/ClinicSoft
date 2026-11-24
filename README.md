# Gestor de Citas — documentación técnica

Sistema de gestión de citas médicas construido sobre el proyecto existente (no reescrito): **Laravel 10**, **Inertia 2**, **Vue 3**, **Vite 6** y **MySQL**. Autorización con `spatie/laravel-permission`.

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
- Las rutas clínicas usan `role:admin|doctor`: `dashboard`, `patients`, `drugs`, `bills`, `appointments`, `medicalhistories`.
- `patient` solo puede gestionar su perfil.
- `RoleSeeder` asigna `doctor` a usuarios preexistentes sin rol.
- Los modelos de los módulos están cubiertos por `App\Policies\ModulePolicy`, registrada en `AuthServiceProvider`.
- Todos los `show/edit/update/destroy` usan **route model binding**: es necesario para que `authorizeResource` pase el modelo al Gate; con `string $id` la policy no se resolvía y devolvía 403.

## Reglas de negocio de citas

- Estados (`App\Enums\AppointmentStatus`): `pending`, `confirmed`, `completed`, `cancelled`.
- `completed` marca automáticamente `attended = true`.
- `DELETE appointments/{appointment}` hace **cancelación lógica** (`status=cancelled`, `active=false`), nunca borrado físico; el hueco queda libre.
- Horario configurable en `config/appointments.php`: lunes a viernes, 08:00–18:00, duración 15–240 min, sin solapes para el mismo médico.
- Validación centralizada en `App\Services\AppointmentAvailabilityService`.
- Migración `2026_11_24_000001_improve_appointments_table.php`: `status`, `drug_id` nullable, índices y FK (las FK se aplican en MySQL; SQLite no permite añadirlas a tablas existentes).
- `drug_id` valida contra `drugs`, no contra `users`.

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
php artisan route:list --except-vendor
npm run build
php artisan test --no-ansi
# Opcional, contra la BD MySQL configurada (usa transacción y no deja datos):
php scripts/verify_mysql.php
```

Última ejecución verificada: **45 pruebas, 269 aserciones, todas en verde**; build correcto; `route:list` correcto; autoload sin avisos PSR-4. `ZiggyRoutesTest` comprueba que ningún `route()` del frontend apunte a una ruta inexistente (error que produjo el fallback de `register` al cerrar el registro público).

### Verificación en MySQL real

Se creó la BD `gestordecitas` (utf8mb4 / utf8mb4_unicode_ci) y se ejecutaron las 14 migraciones y `RoleSeeder` sin errores. `php scripts/verify_mysql.php` comprobó:

- FK `appointments.doctor_id → users`, `patient_id → patients`, `drug_id → drugs`.
- Índices `appointments_doctor_date_index`, `appointments_patient_id_index`, `appointments_status_index`, `appointments_date_index` y el único `appointments_doctor_date_start_active_unique`.
- Roles `admin`, `doctor`, `patient`.
- Creación de citas, rechazo de solapes, huecos contiguos, liberación del hueco al cancelar y rechazo de FK inválida.
- La prueba se ejecuta dentro de una transacción que se revierte: no deja registros.

## Despliegue en producción (HTTPS)

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`.
2. `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` (o `strict` si no hay integraciones cross-site).
3. Ejecutar `php artisan migrate --force` en MySQL y `php artisan db:seed --class=RoleSeeder`.
4. `npm run build` y publicar `public/build`.
5. Permisos de escritura en `storage/` y `bootstrap/cache/`.
6. Terminar TLS en el servidor/proxy y revisar `composer audit` antes de cada release.

## Limitaciones conocidas

- Las pruebas automatizadas usan SQLite en memoria; la verificación sobre MySQL se ejecuta por separado con `scripts/verify_mysql.php`.
- Los módulos `Doctor`, `Prescription` y `BillDetail` siguen siendo stubs heredados.
- ApexCharts genera un chunk >500 kB (aviso de Vite, no error).
- PHP 8.5 emite un aviso no bloqueante en `vendor/nunomaduro/collision` (`ReflectionMethod::setAccessible()` deprecated).
- No hay pruebas E2E en navegador; la verificación frontend es compilación + pruebas Inertia.
- Los cambios de las fases P0–P5 quedan versionados en Git; el estado de `origin` depende del último push.
