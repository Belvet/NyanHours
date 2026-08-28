<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = requireAdmin($app['pdo']);
$details = (new TimeEntryRepository($app['pdo']))->reportByActivity();
$people = []; $grandTotal = 0;
foreach ($details as $row) {
    $userId=(int)$row['user_id']; $clientId=(int)$row['client_id']; $minutes=(int)$row['total_minutes'];
    $people[$userId]['name']=$row['user_name'];
    $people[$userId]['total']=($people[$userId]['total']??0)+$minutes;
    $people[$userId]['clients'][$clientId]['name']=$row['client_name'];
    $people[$userId]['clients'][$clientId]['color']=$row['client_color'];
    $people[$userId]['clients'][$clientId]['total']=($people[$userId]['clients'][$clientId]['total']??0)+$minutes;
    $people[$userId]['clients'][$clientId]['activities'][]=[
        'name'=>$row['activity'],
        'date'=>$row['work_date'],
        'minutes'=>$minutes,
    ];
    $grandTotal += $minutes;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Resumen de horas | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><a class="button-secondary" href="/admin/">Administración</a></header>
<main class="container"><div class="page-heading"><div><h1>Resumen de horas</h1><p class="muted">Tiempo acumulado por persona y cliente.</p></div></div>
<div class="stats stats-single"><div class="stat"><strong><?= e(formatMinutes($grandTotal)) ?></strong><span>Total de todo el equipo</span></div></div>
<section class="panel table-wrap">
<?php if ($people === []): ?><div class="empty-state"><h2>Todavía no hay horas registradas</h2><p>Los registros del equipo aparecerán aquí.</p></div>
<?php else: ?><div class="report-tree">
<?php foreach($people as $person):?><section class="person-report"><header><h2><?=e($person['name'])?></h2><strong><?=e(formatMinutes((int)$person['total']))?></strong></header>
<?php foreach($person['clients'] as $client):?><div class="client-report"><div class="client-subtotal"><strong class="client-name" style="--client-color:<?=e($client['color'])?>"><i></i><?=e($client['name'])?></strong><span>Subtotal: <strong><?=e(formatMinutes((int)$client['total']))?></strong></span></div>
<?php foreach($client['activities'] as $activity):?><div class="activity-row"><span><?=e($activity['name'])?></span><time datetime="<?=e($activity['date'])?>"><?=e(date('d/m/Y',strtotime((string)$activity['date'])))?></time><strong><?=e(formatMinutes((int)$activity['minutes']))?></strong></div><?php endforeach;?>
</div><?php endforeach;?></section><?php endforeach;?>
</div><?php endif; ?>
</section></main></body></html>
