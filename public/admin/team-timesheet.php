<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$admin = requireAdmin($app['pdo']);
$entryRepository = new TimeEntryRepository($app['pdo']);
$clientRepository = new ClientRepository($app['pdo']);
$userRepository = new UserRepository($app['pdo']);
$errors = [];

$clients = $clientRepository->all();
$clientIds = array_map(static fn (array $client): int => (int) $client['id'], $clients);
$clientId = filter_var($_POST['client_id'] ?? $_GET['client_id'] ?? 0, FILTER_VALIDATE_INT);
$clientId = is_int($clientId) && in_array($clientId, $clientIds, true) ? $clientId : 0;
$selectedClient = $clientId > 0 ? $clientRepository->findById($clientId) : null;

$requestedWeek = (string) ($_POST['week'] ?? $_GET['week'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedWeek)) $requestedWeek = date('Y-m-d');
$anchor = DateTimeImmutable::createFromFormat('!Y-m-d', $requestedWeek) ?: new DateTimeImmutable('today');
$weekStart = $anchor->modify('monday this week');
$weekEnd = $weekStart->modify('+6 days');
$dates = [];
for ($day = 0; $day < 7; $day++) $dates[] = $weekStart->modify("+$day days");

$users = $userRepository->all();
$userIds = array_map(static fn (array $user): int => (int) $user['id'], $users);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    if ($selectedClient === null) $errors[] = 'Seleccioná un cliente válido.';
    $submitted = is_array($_POST['hours'] ?? null) ? $_POST['hours'] : [];
    $changes = [];
    foreach ($submitted as $userIdRaw => $dayValues) {
        $userId = filter_var($userIdRaw, FILTER_VALIDATE_INT);
        if (!is_int($userId) || !in_array($userId, $userIds, true) || !is_array($dayValues)) continue;
        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');
            $rawValue = (string) ($dayValues[$dateString] ?? '');
            $minutes = parseDuration($rawValue);
            if ($minutes === null) {
                $errors[] = "El valor '$rawValue' no es válido. Usá 1, 1.5 o 1:30.";
                continue;
            }
            if (!$entryRepository->isPeriodClosed($dateString)) $changes[] = [$userId, $dateString, $minutes];
        }
    }
    if ($errors === [] && $selectedClient !== null) {
        $app['pdo']->beginTransaction();
        try {
            foreach ($changes as [$userId, $dateString, $minutes]) {
                $entryRepository->setTimesheetTotal($userId, $clientId, $dateString, $minutes);
            }
            $app['pdo']->commit();
            flash('success', 'Planilla del equipo guardada correctamente.');
            redirect('/admin/team-timesheet.php?' . http_build_query([
                'client_id' => $clientId,
                'week' => $weekStart->format('Y-m-d'),
            ]));
        } catch (DomainException $exception) {
            $app['pdo']->rollBack();
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            $app['pdo']->rollBack();
            throw $exception;
        }
    }
}

$grid = [];
if ($selectedClient !== null) {
    foreach ($entryRepository->forClientPeriod($clientId, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')) as $entry) {
        $grid[(int) $entry['user_id']][(string) $entry['work_date']] = (int) $entry['duration_minutes'];
    }
}
$closedDates = [];
foreach ($dates as $date) $closedDates[$date->format('Y-m-d')] = $entryRepository->isPeriodClosed($date->format('Y-m-d'));
$dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$monthNames = [1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic'];
$flashes = consumeFlashes();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Planilla de equipo | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($admin); ?>
<main class="container timesheet-container">
<div class="timesheet-heading"><div><h1>Planilla de equipo</h1><p class="muted">Revisá y editá las horas de todo el equipo por cliente.</p></div><?php if($selectedClient !== null):?><div class="week-nav"><a href="?<?=e(http_build_query(['client_id'=>$clientId,'week'=>$weekStart->modify('-7 days')->format('Y-m-d')]))?>">‹</a><span><?=e($weekStart->format('d/m'))?> – <?=e($weekEnd->format('d/m/Y'))?></span><a href="?<?=e(http_build_query(['client_id'=>$clientId,'week'=>$weekStart->modify('+7 days')->format('Y-m-d')]))?>">›</a></div><?php endif;?></div>
<form class="panel date-filter team-client-filter" method="get"><div><label for="client_id">Cliente</label><select id="client_id" name="client_id" required><option value="">Seleccionar cliente</option><?php foreach($clients as $client):?><option value="<?=e($client['id'])?>" <?= (int)$client['id']===$clientId?'selected':'' ?>><?=e($client['name'])?><?=!(bool)$client['active']?' (Inactivo)':''?></option><?php endforeach;?></select></div><input type="hidden" name="week" value="<?=e($weekStart->format('Y-m-d'))?>"><button type="submit">Ver planilla</button></form>
<?php foreach($flashes as $message):?><div class="alert alert-<?=e($message['type'])?>"><?=e($message['message'])?></div><?php endforeach;?>
<?php if($errors!==[]):?><div class="alert alert-error"><ul><?php foreach(array_unique($errors) as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<?php if($selectedClient===null):?><section class="panel empty-state"><h2>Elegí un cliente</h2><p>Seleccioná el cliente para ver cuánto cargó cada integrante por día.</p></section>
<?php else:?><div class="selected-client"><span class="client-dot" style="background:<?=e($selectedClient['color'])?>"></span><strong style="color:<?=e($selectedClient['color'])?>"><?=e($selectedClient['name'])?></strong></div>
<form method="post" id="timesheet-form"><input type="hidden" name="client_id" value="<?=e($clientId)?>"><input type="hidden" name="week" value="<?=e($weekStart->format('Y-m-d'))?>"><?=csrfField()?>
<section class="timesheet panel table-wrap"><table><thead><tr><th class="client-column">Usuario</th><?php foreach($dates as $index=>$date):?><th class="day-column <?=$index>=5?'weekend':''?>"><span><?=e($dayNames[$index])?></span><strong><?=e($date->format('d'))?> <?=e($monthNames[(int)$date->format('n')])?></strong></th><?php endforeach;?><th>Total</th></tr></thead><tbody>
<?php foreach($users as $teamUser):?><tr data-client-row><td class="client-column"><strong><?=e($teamUser['name'])?></strong><small><?=e(strtoupper((string)$teamUser['role']))?><?=!(bool)$teamUser['active']?' · Inactivo':''?></small></td>
<?php foreach($dates as $index=>$date):$dateString=$date->format('Y-m-d');$minutes=$grid[(int)$teamUser['id']][$dateString]??0;?><td class="<?=$index>=5?'weekend':''?>"><input class="hours-input" name="hours[<?=e($teamUser['id'])?>][<?=e($dateString)?>]" value="<?=e(durationInput($minutes))?>" placeholder="0:00" inputmode="decimal" <?=$closedDates[$dateString]?'disabled title="Período cerrado"':''?>></td><?php endforeach;?>
<td class="row-total">0:00</td></tr><?php endforeach;?></tbody><tfoot><tr><th>Total</th><?php foreach($dates as $index=>$date):?><th class="day-total <?=$index>=5?'weekend':''?>">0:00</th><?php endforeach;?><th id="week-total">0:00</th></tr></tfoot></table></section>
<div class="timesheet-actions"><span class="muted">Los eventos detallados no pueden reducirse por debajo de su total en Time Tracker.</span><button type="submit">Guardar planilla</button></div></form><?php endif;?>
</main><script src="/assets/js/timesheet.js?v=4"></script></body></html>
