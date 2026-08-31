<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$user = requireLogin($app['pdo']);
$repository = new TimeEntryRepository($app['pdo']);
$clients = (new ClientRepository($app['pdo']))->allActive();
$values = ['description'=>trim((string)($_POST['description']??'')),'client_id'=>(string)($_POST['client_id']??''),'work_date'=>(string)($_POST['work_date']??date('Y-m-d')),'duration'=>(string)($_POST['duration']??'')];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $clientId = filter_var($values['client_id'], FILTER_VALIDATE_INT);
    $client = is_int($clientId) ? (new ClientRepository($app['pdo']))->findById($clientId) : null;
    $minutes = parseDuration($values['duration']);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values['work_date']);
    if ($values['description'] === '' || mb_strlen($values['description']) > 1000) $errors[] = 'Escribí una actividad de hasta 1000 caracteres.';
    if ($client === null || !(bool)$client['active']) $errors[] = 'Seleccioná un cliente activo.';
    if ($minutes === null || $minutes < 1) $errors[] = 'Ingresá una duración válida mayor que cero.';
    if ($date === false || $date->format('Y-m-d') !== $values['work_date']) $errors[] = 'Ingresá una fecha válida.';
    elseif ($repository->isPeriodClosed($values['work_date'])) $errors[] = 'Ese período está cerrado.';
    if ($errors === []) {
        $repository->createTrackerEvent((int)$user['id'], $clientId, $values['work_date'], $minutes, $values['description']);
        flash('success', 'Actividad agregada correctamente.');
        redirect('/time-tracker.php');
    }
}
$events = $repository->trackerEvents((int)$user['id']);
$groups = [];
foreach ($events as $event) $groups[(string)$event['work_date']][] = $event;
$flashes = consumeFlashes();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="<?=e(csrfToken())?>"><title>Time Tracker | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><nav class="actions"><a class="button-secondary" href="/timesheet.php">Planilla semanal</a></nav></header>
<main class="container tracker-container"><h1>Time Tracker</h1><p class="muted">Registrá actividades individuales. También aparecen las horas cargadas desde la planilla.</p>
<?php foreach($flashes as $message):?><div class="alert alert-<?=e($message['type'])?>"><?=e($message['message'])?></div><?php endforeach;?>
<?php if($errors!==[]):?><div class="alert alert-error"><ul><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="post" class="tracker-entry panel"><?=csrfField()?>
    <div class="tracker-activity"><label for="description">¿En qué trabajaste?</label><input id="description" name="description" value="<?=e($values['description'])?>" placeholder="Ej.: Diseño de newsletter" maxlength="1000" required></div>
    <div><label for="client_id">Cliente</label><select id="client_id" name="client_id" required><option value="">Seleccionar</option><?php foreach($clients as $client):?><option value="<?=e($client['id'])?>" style="color:<?=e($client['color'])?>" <?=(string)$client['id']===$values['client_id']?'selected':''?>><?=e($client['name'])?></option><?php endforeach;?></select></div>
    <div><label for="work_date">Fecha</label><input id="work_date" name="work_date" type="date" value="<?=e($values['work_date'])?>" required></div>
    <div><label for="duration">Duración</label><input id="duration" name="duration" class="duration-value-input" value="<?=e($values['duration'])?>" placeholder="1:00" inputmode="decimal" pattern="[0-9:.,]+" title="Usá números, por ejemplo 1, 1.5 o 1:30" required></div>
    <button type="submit">Agregar</button>
</form>
<div class="tracker-list">
<?php if($groups===[]):?><section class="panel empty-state"><h2>No hay actividades</h2><p>Agregá una actividad o completá la planilla semanal.</p></section><?php endif;?>
<?php foreach($groups as $date=>$dayEvents): $dayTotal=array_sum(array_map(static fn(array $event):int=>(int)$event['duration_minutes'],$dayEvents)); ?>
<section class="tracker-day"><header><time datetime="<?=e($date)?>" data-local-date="<?=e($date)?>"><?=e(date('d/m/Y',strtotime($date)))?></time><strong>Total: <?=e(formatMinutes($dayTotal))?></strong></header>
<?php foreach($dayEvents as $event):?><article class="tracker-event"><div class="tracker-event-main"><button type="button" class="inline-description <?=empty($event['description'])?'is-empty':''?>" data-entry-id="<?=e($event['id'])?>" data-description="<?=e($event['description'])?>" title="Hacé clic para editar"><?=e($event['description'] ?: 'No detallado')?></button><span class="client-label" style="--client-color:<?=e($event['client_color'])?>"><i class="client-dot"></i><?=e($event['client_name'])?></span></div><button type="button" class="inline-duration" data-entry-id="<?=e($event['id'])?>" data-minutes="<?=e($event['duration_minutes'])?>" title="Hacé clic para editar"><?=e(formatMinutes((int)$event['duration_minutes']))?></button><div class="actions"><form method="post" action="/time-entries/delete.php" onsubmit="return confirm('¿Eliminar esta actividad?')"><?=csrfField()?><input type="hidden" name="id" value="<?=e($event['id'])?>"><button class="button-danger" type="submit">Eliminar</button></form></div></article><?php endforeach;?>
</section><?php endforeach;?>
</div></main><script src="/assets/js/time-tracker.js?v=3"></script></body></html>
