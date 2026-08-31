<?php
declare(strict_types=1);

/** @var array{config: array{app: array{name: string}}, pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$user = requireLogin($app['pdo']);
$entryRepository = new TimeEntryRepository($app['pdo']);
$earningsPeriod = in_array(($_GET['earnings_period'] ?? ''), ['week', 'month', 'custom'], true)
    ? (string) $_GET['earnings_period'] : 'month';
$today = new DateTimeImmutable('today');
$earningsFrom = $earningsPeriod === 'week' ? $today->modify('monday this week')->format('Y-m-d') : $today->format('Y-m-01');
$earningsTo = $today->format('Y-m-d');
$earningsError = null;
if ($earningsPeriod === 'custom') {
    $earningsFrom = trim((string) ($_GET['earnings_from'] ?? $today->format('Y-m-01')));
    $earningsTo = trim((string) ($_GET['earnings_to'] ?? $today->format('Y-m-d')));
    $validDate = static function (string $value): bool {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    };
    if (!$validDate($earningsFrom) || !$validDate($earningsTo) || $earningsFrom > $earningsTo) {
        $earningsError = 'Ingresá un rango de fechas válido.';
        $earningsFrom = $today->format('Y-m-01');
        $earningsTo = $today->format('Y-m-d');
    }
}
$summary = $earningsError === null
    ? $entryRepository->earningsForUserBetween((int) $user['id'], $earningsFrom, $earningsTo)
    : ['total_minutes' => 0, 'total_earnings' => 0];
$entries = $earningsError === null
    ? $entryRepository->forUserBetween((int) $user['id'], $earningsFrom, $earningsTo)
    : [];
$totalMinutes = (int) $summary['total_minutes'];
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
    <form class="panel date-filter dashboard-filter" method="get">
        <div><label for="earnings_period">Período</label><select id="earnings_period" name="earnings_period"><option value="week" <?=$earningsPeriod==='week'?'selected':''?>>Esta semana</option><option value="month" <?=$earningsPeriod==='month'?'selected':''?>>Este mes</option><option value="custom" <?=$earningsPeriod==='custom'?'selected':''?>>Personalizado</option></select></div>
        <div class="earnings-custom-date"><label for="earnings_from">Desde</label><input id="earnings_from" name="earnings_from" type="date" value="<?=e($earningsFrom)?>"></div>
        <div class="earnings-custom-date"><label for="earnings_to">Hasta</label><input id="earnings_to" name="earnings_to" type="date" value="<?=e($earningsTo)?>"></div>
        <button type="submit">Filtrar</button>
    </form>
    <?php if($earningsError!==null):?><div class="alert alert-error"><?=e($earningsError)?></div><?php endif;?>
    <p class="earnings-period-label">Período seleccionado: <time datetime="<?=e($earningsFrom)?>" data-local-date="<?=e($earningsFrom)?>"><?=e(date('d/m/Y',strtotime($earningsFrom)))?></time> — <time datetime="<?=e($earningsTo)?>" data-local-date="<?=e($earningsTo)?>"><?=e(date('d/m/Y',strtotime($earningsTo)))?></time></p>
    <div class="stats <?=in_array($user['role'], ['admin','operator'], true)?'dashboard-stats':'stats-single'?>"><div class="stat"><strong><?= e(formatMinutes($totalMinutes)) ?></strong><span>Tiempo total registrado</span></div><?php if(in_array($user['role'], ['admin','operator'], true)):?><div class="stat earnings-stat"><strong>USD <?=e(number_format((float)$summary['total_earnings'], 2, ',', '.'))?></strong><span>Mis ganancias</span></div><?php endif;?></div>
    <section class="panel table-wrap">
        <?php if ($entries === []): ?><div class="empty-state"><h2>No hay tareas en este período</h2><p>Probá con otro período o registrá nuevas horas.</p><a class="button" href="/timesheet.php">Abrir planilla</a></div>
        <?php else: ?><table><thead><tr><th>Fecha</th><th>Cliente</th><th>Tiempo</th><th>Descripción</th></tr></thead><tbody>
        <?php foreach ($entries as $entry): ?><tr>
            <td><time datetime="<?=e($entry['work_date'])?>" data-local-date="<?=e($entry['work_date'])?>"><?= e(date('d/m/Y', strtotime((string) $entry['work_date']))) ?></time></td><td><strong class="client-name" style="--client-color:<?=e($entry['client_color'])?>"><i></i><?= e($entry['client_name']) ?></strong></td>
            <td><strong><?= e(formatMinutes((int) $entry['total_minutes'])) ?></strong></td><td class="description-cell"><?= e($entry['description'] ?: 'No detallado') ?></td>
        </tr><?php endforeach; ?></tbody></table><?php endif; ?>
    </section>
    <?php if (in_array($user['role'], ['admin','owner'], true)): ?>
        <section class="panel"><h2>Administración</h2><p>Consultá todo el equipo, los clientes y los totales acumulados.</p><div class="actions"><a class="button" href="/admin/users/">Administrar usuarios</a><a class="button-secondary" href="/admin/reports.php">Ver resumen de horas</a></div></section>
    <?php endif; ?>
</main>
</body>
</html>
