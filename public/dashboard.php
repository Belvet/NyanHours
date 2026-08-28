<?php
declare(strict_types=1);

/** @var array{config: array{app: array{name: string}}, pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$user = requireLogin($app['pdo']);
$entryRepository = new TimeEntryRepository($app['pdo']);
$entries = $entryRepository->forUser((int) $user['id']);
$totalMinutes = $entryRepository->totalMinutesForUser((int) $user['id']);
$flashes = consumeFlashes();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel | <?= e($app['config']['app']['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-layout">
<?php renderSidebar($user); ?>
<header class="topbar">
    <a class="brand" href="/dashboard.php">NyanHours</a>
    <div class="user-menu"><span><?= e($user['name']) ?></span>
        <form method="post" action="/logout.php"><?= csrfField() ?><button class="button-secondary" type="submit">Cerrar sesión</button></form>
    </div>
</header>
<main class="container">
    <div class="page-heading dashboard-heading"><div><h1>Hola, <?= e($user['name']) ?></h1><p class="muted">Registrá y consultá tus horas trabajadas.</p></div><img src="/assets/img/nyansei-mascot-hi.png" alt="" aria-hidden="true"><div class="actions"><a class="button" href="/time-tracker.php">Time Tracker</a><a class="button-secondary" href="/timesheet.php">Planilla semanal</a></div></div>
    <?php foreach ($flashes as $message): ?><div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div><?php endforeach; ?>
    <div class="stats stats-single"><div class="stat"><strong><?= e(formatMinutes($totalMinutes)) ?></strong><span>Tiempo total registrado</span></div></div>
    <section class="panel table-wrap">
        <?php if ($entries === []): ?><div class="empty-state"><h2>Todavía no registraste horas</h2><p>Completá tu primera planilla semanal para comenzar.</p><a class="button" href="/timesheet.php">Abrir planilla</a></div>
        <?php else: ?><table><thead><tr><th>Fecha</th><th>Cliente</th><th>Tiempo</th><th>Descripción</th></tr></thead><tbody>
        <?php foreach ($entries as $entry): ?><tr>
            <td><?= e(date('d/m/Y', strtotime((string) $entry['work_date']))) ?></td><td><strong class="client-name" style="--client-color:<?=e($entry['client_color'])?>"><i></i><?= e($entry['client_name']) ?></strong></td>
            <td><strong><?= e(formatMinutes((int) $entry['total_minutes'])) ?></strong></td><td class="description-cell"><?= e($entry['description'] ?: '—') ?></td>
        </tr><?php endforeach; ?></tbody></table><?php endif; ?>
    </section>
    <?php if ($user['role'] === 'admin'): ?>
        <section class="panel"><h2>Administración</h2><p>Consultá todo el equipo, los clientes y los totales acumulados.</p><div class="actions"><a class="button" href="/admin/">Abrir panel administrativo</a><a class="button-secondary" href="/admin/reports.php">Ver resumen de horas</a></div></section>
    <?php endif; ?>
</main>
</body>
</html>
