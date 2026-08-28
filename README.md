# NyanHours

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=111)
![Status](https://img.shields.io/badge/status-MVP-5046E5)

Aplicación web de gestión de horas para equipos pequeños. Combina una planilla semanal con un registro detallado de actividades, inspirándose en la experiencia de Clockify sin depender de frameworks.

## Características

- Timesheet semanal por cliente con navegación entre semanas.
- Time Tracker con múltiples actividades por día y cliente.
- Edición inline de actividades y duraciones.
- Duraciones inteligentes: `130`, `1:3` y `1.5` se normalizan a `1:30`.
- Sincronización automática entre Timesheet y Time Tracker.
- Clientes identificados por colores personalizables.
- Roles `ADMIN` y `OPERADOR` con autorización en el backend.
- Gestión de usuarios, tarifas por hora y estados de cuenta.
- Gestión de clientes activos e inactivos.
- Reportes con totales por persona, cliente y actividad.
- Protección CSRF, sesiones seguras, PDO y contraseñas con `password_hash()`.
- Interfaz responsive con navegación lateral persistente.
- Interfaz bilingüe español/inglés con preferencia persistente.

## Stack

| Capa | Tecnología |
| --- | --- |
| Backend | PHP 8.2+ |
| Base de datos | MySQL 8 / Percona 8 / MariaDB 10.4+ |
| Frontend | HTML5, CSS3 y JavaScript vanilla |
| Acceso a datos | PDO con consultas preparadas |

No utiliza React, Vue, Laravel ni dependencias de Composer o npm.

## Estructura

```text
NyanHours/
├── app/                 # Autenticación, seguridad, helpers y repositorios
├── config/              # Plantilla de configuración privada
├── database/            # Esquema, migraciones y scripts CLI
├── public/              # Raíz web, pantallas y assets
│   ├── admin/           # Gestión y reportes administrativos
│   ├── assets/          # CSS y JavaScript
│   └── time-entries/    # Acciones sobre registros de tiempo
└── storage/             # Archivos locales no públicos
```

## Instalación local

### Requisitos

- PHP 8.2 o superior con `pdo_mysql`.
- MySQL 8, Percona Server 8 o MariaDB 10.4+.

### 1. Crear la base

```sql
CREATE DATABASE nyan_hours
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'nyan_hours_user'@'localhost'
    IDENTIFIED BY 'una-clave-local-segura';

GRANT ALL PRIVILEGES ON nyan_hours.*
    TO 'nyan_hours_user'@'localhost';
```

Importar [`database/schema.sql`](database/schema.sql) dentro de `nyan_hours`.

### 2. Configurar la conexión

```powershell
Copy-Item config/config.example.php config/config.local.php
```

Completar la copia con los datos locales. `config/config.local.php` está ignorado por Git y nunca debe publicarse.

### 3. Crear el primer administrador

```powershell
php database/seed-admin.php admin@example.com "una-contraseña-segura" "Administrador"
```

### 4. Ejecutar

```powershell
php -S 127.0.0.1:8080 -t public
```

Abrir [http://127.0.0.1:8080](http://127.0.0.1:8080).

## Seguridad

- Hash de contraseñas mediante `password_hash()` y `password_verify()`.
- Consultas preparadas y emulación desactivada en PDO.
- Tokens CSRF para operaciones que modifican datos.
- Escape de HTML para prevenir XSS.
- Regeneración del ID de sesión al iniciar sesión.
- Comprobación de propiedad: cada usuario solamente modifica sus registros.
- Restricciones administrativas aplicadas en el servidor, no solo en la interfaz.
- Credenciales locales excluidas del repositorio.

## Estado del proyecto

NyanHours se encuentra en desarrollo activo. El MVP incluye autenticación, roles, clientes, Timesheet, Time Tracker y reportes. Próximas etapas previstas:

- Filtros avanzados y rangos de fechas.
- Cálculo de pagos según tarifa.
- Exportación CSV.
- Cierre y reapertura de períodos mensuales.
- Pruebas automatizadas.
- Despliegue en hosting tradicional con PHP-FPM.

## Autor

Proyecto personal desarrollado como aplicación real de gestión interna y como parte de un portfolio de desarrollo web.
