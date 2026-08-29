<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = requireAdmin($app['pdo']);
$dateFrom=trim((string)($_GET['date_from']??'')); $dateTo=trim((string)($_GET['date_to']??'')); $filterErrors=[];
$validDate=static function(string $value):bool{$date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);return $date!==false&&$date->format('Y-m-d')===$value;};
if(($dateFrom==='')!==($dateTo===''))$filterErrors[]='Completá las dos fechas para aplicar el filtro.';
elseif($dateFrom!==''&&(!$validDate($dateFrom)||!$validDate($dateTo)))$filterErrors[]='Ingresá un rango de fechas válido.';
elseif($dateFrom!==''&&$dateFrom>$dateTo)$filterErrors[]='La fecha desde no puede ser posterior a la fecha hasta.';
$filterActive=$filterErrors===[]&&$dateFrom!=='';
$details = (new TimeEntryRepository($app['pdo']))->reportByActivity($filterActive?$dateFrom:null,$filterActive?$dateTo:null);
$clientsReport = []; $grandTotal = 0;
foreach ($details as $row) {
    $userId=(int)$row['user_id']; $clientId=(int)$row['client_id']; $minutes=(int)$row['total_minutes'];
    $clientsReport[$clientId]['name']=$row['client_name'];
    $clientsReport[$clientId]['color']=$row['client_color'];
    $clientsReport[$clientId]['total']=($clientsReport[$clientId]['total']??0)+$minutes;
    $clientsReport[$clientId]['users'][$userId]['name']=$row['user_name'];
    $clientsReport[$clientId]['users'][$userId]['total']=($clientsReport[$clientId]['users'][$userId]['total']??0)+$minutes;
    $clientsReport[$clientId]['users'][$userId]['activities'][]=[
        'name'=>$row['activity'],
        'date'=>$row['work_date'],
        'minutes'=>$minutes,
    ];
    $grandTotal += $minutes;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="<?=e(csrfToken())?>"><title>Resumen de horas | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><a class="button-secondary" href="/admin/">Administración</a></header>
<main class="container"><div class="page-heading"><div><h1>Resumen de horas</h1><p class="muted">Tiempo acumulado por cliente, usuario y actividad.</p></div></div>
<form class="panel date-filter" method="get"><div><label for="date_from">Desde</label><input id="date_from" name="date_from" type="date" value="<?=e($dateFrom)?>"></div><div><label for="date_to">Hasta</label><input id="date_to" name="date_to" type="date" value="<?=e($dateTo)?>"></div><button type="submit">Filtrar</button><?php if($dateFrom!==''||$dateTo!==''):?><a class="button-secondary" href="/admin/reports.php">Limpiar</a><?php endif;?></form>
<?php foreach($filterErrors as $error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
<?php if($filterActive):?><p class="filter-summary">Período seleccionado: <strong><?=e(date('d/m/Y',strtotime($dateFrom)))?></strong> — <strong><?=e(date('d/m/Y',strtotime($dateTo)))?></strong></p><?php endif;?>
<div class="stats stats-single"><div class="stat"><strong><?= e(formatMinutes($grandTotal)) ?></strong><span>Total de todo el equipo</span></div></div>
<section class="panel table-wrap">
<?php if ($clientsReport === []): ?><div class="empty-state"><h2>Todavía no hay horas registradas</h2><p>Los registros del equipo aparecerán aquí.</p></div>
<?php else: ?><div class="report-tree">
<?php foreach($clientsReport as $reportClientId=>$client):?><section class="person-report"><header><h2 class="client-name" style="--client-color:<?=e($client['color'])?>"><i></i><?=e($client['name'])?></h2><strong><?=e(formatMinutes((int)$client['total']))?></strong></header>
<?php foreach($client['users'] as $reportUserId=>$person):?><div class="client-report"><div class="client-subtotal"><strong><?=e($person['name'])?></strong><span>Subtotal: <strong><?=e(formatMinutes((int)$person['total']))?></strong></span></div>
<?php foreach($person['activities'] as $activity):?><div class="activity-row"><button type="button" class="admin-inline-activity" data-user-id="<?=e($reportUserId)?>" data-client-id="<?=e($reportClientId)?>" data-date="<?=e($activity['date'])?>" data-activity="<?=e($activity['name'])?>" title="Hacé clic para editar"><?=e($activity['name'])?></button><time datetime="<?=e($activity['date'])?>"><?=e(date('d/m/Y',strtotime((string)$activity['date'])))?></time><strong><?=e(formatMinutes((int)$activity['minutes']))?></strong></div><?php endforeach;?>
</div><?php endforeach;?></section><?php endforeach;?>
</div><?php endif; ?>
</section></main><script src="/assets/js/admin-reports.js?v=1"></script></body></html>
