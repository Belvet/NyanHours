<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$user = requireLogin($app['pdo']);
$requestedWeek = (string) ($_POST['week'] ?? $_GET['week'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedWeek)) $requestedWeek = date('Y-m-d');
$anchor = new DateTimeImmutable($requestedWeek);
$weekStart = $anchor->modify('monday this week');
$weekEnd = $weekStart->modify('+6 days');
$dates = [];
for ($day = 0; $day < 7; $day++) $dates[] = $weekStart->modify("+$day days");

$entryRepository = new TimeEntryRepository($app['pdo']);
$clientRepository = new ClientRepository($app['pdo']);
$clients = $clientRepository->forTimesheet((int) $user['id'], $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
$clientIds = array_map(static fn (array $client): int => (int) $client['id'], $clients);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $submitted = is_array($_POST['hours'] ?? null) ? $_POST['hours'] : [];
    $changes = [];
    foreach ($submitted as $clientIdRaw => $dayValues) {
        $clientId = filter_var($clientIdRaw, FILTER_VALIDATE_INT);
        if (!is_int($clientId) || !in_array($clientId, $clientIds, true) || !is_array($dayValues)) continue;
        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');
            $rawValue = (string) ($dayValues[$dateString] ?? '');
            $minutes = parseDuration($rawValue);
            if ($minutes === null) {
                $errors[] = "El valor '$rawValue' no es válido. Usá 1, 1.5 o 1:30.";
                continue;
            }
            if (!$entryRepository->isPeriodClosed($dateString)) $changes[] = [$clientId, $dateString, $minutes];
        }
    }
    if ($errors === []) {
        $app['pdo']->beginTransaction();
        try {
            foreach ($changes as [$clientId, $dateString, $minutes]) {
                $entryRepository->setTimesheetTotal((int) $user['id'], $clientId, $dateString, $minutes);
            }
            $app['pdo']->commit();
            flash('success', 'Semana guardada correctamente.');
            redirect('/timesheet.php?week=' . $weekStart->format('Y-m-d'));
        } catch (DomainException $exception) {
            $app['pdo']->rollBack();
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            $app['pdo']->rollBack();
            throw $exception;
        }
    }
}

$entries = $entryRepository->forWeek((int) $user['id'], $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
$grid = [];
foreach ($entries as $entry) $grid[(int) $entry['client_id']][(string) $entry['work_date']] = (int) $entry['duration_minutes'];
$closedDates = [];
foreach ($dates as $date) $closedDates[$date->format('Y-m-d')] = $entryRepository->isPeriodClosed($date->format('Y-m-d'));
$dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$monthNames = [1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic'];
$flashes = consumeFlashes();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Planilla semanal | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><nav class="actions"><a class="button-secondary" href="/time-tracker.php">Time Tracker</a><?php if ($user['role'] === 'admin'): ?><a class="button-secondary" href="/admin/">Administración</a><?php endif; ?><a class="button-secondary" href="/dashboard.php">Mi historial</a></nav></header>
<main class="container timesheet-container">
<div class="timesheet-heading"><div><h1>Planilla semanal</h1><p class="muted">Horas de <?= e($user['name']) ?></p></div><div class="week-nav"><a href="?week=<?= e($weekStart->modify('-7 days')->format('Y-m-d')) ?>">‹</a><span><?= e($weekStart->format('d/m')) ?> – <?= e($weekEnd->format('d/m/Y')) ?></span><a href="?week=<?= e($weekStart->modify('+7 days')->format('Y-m-d')) ?>">›</a></div></div>
<?php foreach ($flashes as $message): ?><div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div><?php endforeach; ?>
<?php if ($errors !== []): ?><div class="alert alert-error"><ul><?php foreach (array_unique($errors) as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($clients === []): ?><div class="panel empty-state"><h2>No hay clientes activos</h2><p>Un administrador debe crear un cliente antes de cargar horas.</p></div><?php else: ?>
<form method="post" id="timesheet-form"><input type="hidden" name="week" value="<?= e($weekStart->format('Y-m-d')) ?>"><?= csrfField() ?>
<section class="timesheet panel table-wrap"><table><thead><tr><th class="client-column">Cliente</th><?php foreach ($dates as $index => $date): ?><th class="day-column <?= $index >= 5 ? 'weekend' : '' ?>"><span><?= e($dayNames[$index]) ?></span><strong><?= e($date->format('d')) ?> <?= e($monthNames[(int)$date->format('n')]) ?></strong></th><?php endforeach; ?><th>Total</th></tr></thead><tbody>
<?php foreach ($clients as $client): ?><tr data-client-row><td class="client-column"><span class="client-dot" style="background:<?=e($client['color'])?>"></span><strong style="color:<?=e($client['color'])?>"><?= e($client['name']) ?></strong><?php if (!(bool)$client['active']): ?><small>Inactivo</small><?php endif; ?></td>
<?php foreach ($dates as $index => $date): $dateString=$date->format('Y-m-d'); $minutes=$grid[(int)$client['id']][$dateString] ?? 0; ?><td class="<?= $index >= 5 ? 'weekend' : '' ?>"><input class="hours-input" name="hours[<?= e($client['id']) ?>][<?= e($dateString) ?>]" value="<?= e(durationInput($minutes)) ?>" placeholder="0:00" inputmode="decimal" <?= $closedDates[$dateString] ? 'disabled title="Período cerrado"' : '' ?>></td><?php endforeach; ?>
<td class="row-total">0:00</td></tr><?php endforeach; ?>
</tbody><tfoot><tr><th>Total</th><?php foreach ($dates as $index => $date): ?><th class="day-total <?= $index >= 5 ? 'weekend' : '' ?>">0:00</th><?php endforeach; ?><th id="week-total">0:00</th></tr></tfoot></table></section>
<div class="timesheet-actions"><span class="muted">Podés escribir 1, 1.5 o 1:30</span><button type="submit">Guardar semana</button></div></form><?php endif; ?>
</main><script src="/assets/js/timesheet.js?v=3"></script></body></html>
