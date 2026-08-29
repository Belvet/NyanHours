# NyanHours

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=111)
![Status](https://img.shields.io/badge/status-MVP-5046E5)

NyanHours is a lightweight time-tracking web application for small teams. It combines a weekly timesheet with detailed activity tracking, inspired by Clockify while remaining framework-free and easy to deploy on traditional PHP hosting.

## Features

- Weekly client-based timesheet with week navigation.
- Detailed Time Tracker with multiple activities per day and client.
- Inline editing for activity descriptions and durations.
- Smart duration parsing: `130`, `1:3`, and `1.5` are normalized to `1:30`.
- Automatic synchronization between Timesheet and Time Tracker.
- Custom color identification for each client.
- `ADMIN` and `OPERATOR` roles with server-side authorization.
- User management, hourly rates, and account activation controls.
- Active and inactive client management.
- Reports grouped by client, team member, and activity, with custom date ranges.
- Editable team timesheet filtered by client.
- Branded PDF exports with date, task, duration, period, and total hours.
- CSRF protection, secure sessions, PDO, and password hashing.
- Responsive interface with persistent sidebar navigation.
- Spanish and English interface with a persistent language preference.

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.2+ |
| Database | MySQL 8 / Percona Server 8 / MariaDB 10.4+ |
| Frontend | HTML5, CSS3, and vanilla JavaScript |
| Data access | PDO with prepared statements |
| PDF export | FPDF 1.9 (bundled) |

The project does not require React, Vue, Laravel, Composer, or npm dependencies. FPDF is bundled so PDF exports also work on traditional shared hosting.

## Project structure

```text
NyanHours/
├── app/                 # Authentication, security, helpers, and repositories
├── config/              # Private configuration template
├── database/            # Schema, migrations, and CLI scripts
├── public/              # Web root, application pages, and assets
│   ├── admin/           # Administrative management and reports
│   ├── assets/          # CSS and JavaScript
│   └── time-entries/    # Time-entry actions and endpoints
└── storage/             # Local non-public files
```

## Local installation

### Requirements

- PHP 8.2 or newer with the `pdo_mysql` extension.
- MySQL 8, Percona Server 8, or MariaDB 10.4+.

### 1. Create the database

```sql
CREATE DATABASE nyan_hours
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'nyan_hours_user'@'localhost'
    IDENTIFIED BY 'your-secure-local-password';

GRANT ALL PRIVILEGES ON nyan_hours.*
    TO 'nyan_hours_user'@'localhost';
```

Import [`database/schema.sql`](database/schema.sql) into the `nyan_hours` database.

### 2. Configure the connection

```powershell
Copy-Item config/config.example.php config/config.local.php
```

Complete the copied file with your local database settings. `config/config.local.php` is ignored by Git and must never be committed.

### 3. Create the first administrator

```powershell
php database/seed-admin.php admin@example.com "a-secure-password" "Administrator"
```

### 4. Run the application

```powershell
php -S 127.0.0.1:8080 -t public
```

Open [http://127.0.0.1:8080](http://127.0.0.1:8080).

## Security

- Password hashing with `password_hash()` and verification with `password_verify()`.
- Prepared statements with native PDO emulation disabled.
- CSRF tokens for state-changing operations.
- HTML escaping to mitigate XSS.
- Session ID regeneration after authentication.
- Ownership checks so users can only modify their own entries.
- Administrative permissions enforced on the server, not only in the UI.
- Local credentials excluded from version control.

## Project status

NyanHours is under active development. The current MVP includes authentication, roles, client management, weekly timesheets, detailed time tracking, inline editing, bilingual navigation, and administrative reports.

Planned improvements:

- Payment calculations based on hourly rates.
- CSV export.
- Monthly period closing and reopening.
- Automated tests.
- Deployment to traditional PHP-FPM hosting.

## Author

Personal project developed as a real-world internal management tool and as part of a web development portfolio.
