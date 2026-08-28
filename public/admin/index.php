<?php
declare(strict_types=1);

/** @var array{config: array{app: array{name: string}}, pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = requireAdmin($app['pdo']);
$counts = [
    'users' => (int) $app['pdo']->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'clients' => (int) $app['pdo']->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
    'entries' => (int) $app['pdo']->query('SELECT COUNT(*) FROM time_entries')->fetchColumn(),
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración | <?= e($app['config']['app']['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-layout">
<?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><nav class="actions"><a class="button" href="/timesheet.php">Mi planilla semanal</a><a class="button-secondary" href="/dashboard.php">Volver al panel</a></nav></header>
<main class="container">
    <h1>Panel administrativo</h1><p class="muted">Sesión de <?= e($user['name']) ?></p>
    <div class="stats">
        <div class="stat"><strong><?= e($counts['users']) ?></strong><span>Usuarios</span></div>
        <div class="stat"><strong><?= e($counts['clients']) ?></strong><span>Clientes</span></div>
        <div class="stat"><strong><?= e($counts['entries']) ?></strong><span>Registros</span></div>
    </div>
    <section class="panel"><h2>Gestión</h2><p>Administrá las cuentas del equipo y los clientes disponibles para registrar horas.</p><div class="actions"><a class="button" href="/admin/users/">Administrar usuarios</a><a class="button" href="/admin/clients/">Administrar clientes</a><a class="button-secondary" href="/admin/reports.php">Resumen de horas</a></div></section>
</main>
</body>
</html>
