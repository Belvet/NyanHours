<?php
declare(strict_types=1);
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$admin = requireOwner($app['pdo']);
$dateFrom = trim((string) ($_GET['date_from'] ?? date('Y-m-01')));
$dateTo = trim((string) ($_GET['date_to'] ?? date('Y-m-d')));
$errors = [];
$validDate = static function (string $value): bool { $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value); return $date!==false && $date->format('Y-m-d')===$value; };
if (!$validDate($dateFrom) || !$validDate($dateTo)) $errors[]='Ingresá un rango de fechas válido.';
elseif ($dateFrom>$dateTo) $errors[]='La fecha desde no puede ser posterior a la fecha hasta.';
$rows=$errors===[]?(new ProfitabilityRepository($app['pdo']))->between($dateFrom,$dateTo):[];
$passiveClients=[]; $ownerClients=[];
$passiveTotals=['minutes'=>0,'billed'=>0.0,'cost'=>0.0,'profit'=>0.0]; $ownerTotals=['minutes'=>0,'billed'=>0.0];
foreach($rows as $row){
    $clientId=(int)$row['client_id']; $minutes=(int)$row['total_minutes']; $billed=(float)$row['billed_amount']; $cost=(float)$row['labor_cost'];
    $isOwner=(bool)$row['is_owner_work'];
    if($isOwner){$target=&$ownerClients;}else{$target=&$passiveClients;}
    $target[$clientId]['name']=$row['client_name']; $target[$clientId]['color']=$row['client_color'];
    $target[$clientId]['minutes']=($target[$clientId]['minutes']??0)+$minutes; $target[$clientId]['billed']=($target[$clientId]['billed']??0)+$billed; $target[$clientId]['cost']=($target[$clientId]['cost']??0)+$cost;
    $target[$clientId]['users'][]=['name'=>$row['user_name'],'hourly_rate'=>(float)$row['user_hourly_rate'],'minutes'=>$minutes,'cost'=>$cost,'profit'=>$billed-$cost]; unset($target);
    if($isOwner){$ownerTotals['minutes']+=$minutes;$ownerTotals['billed']+=$billed;}else{$passiveTotals['minutes']+=$minutes;$passiveTotals['billed']+=$billed;$passiveTotals['cost']+=$cost;}
}
$passiveTotals['profit']=$passiveTotals['billed']-$passiveTotals['cost'];
$totalEarned=$passiveTotals['profit']+$ownerTotals['billed'];
$money=static fn(float $amount):string=>'USD '.number_format($amount,2,',','.');
$averageRate=static fn(array $client):float=>(int)$client['minutes']>0?(float)$client['billed']*60/(int)$client['minutes']:0.0;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Rentabilidad | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($admin);?>
<main class="container"><div class="page-heading"><div><h1>Rentabilidad</h1><p class="muted">Ganancias pasivas del equipo y trabajo directo del OWNER, mostrados por separado.</p></div></div>
<form class="panel date-filter" method="get"><div><label for="date_from">Desde</label><input id="date_from" name="date_from" type="date" value="<?=e($dateFrom)?>" required></div><div><label for="date_to">Hasta</label><input id="date_to" name="date_to" type="date" value="<?=e($dateTo)?>" required></div><button type="submit">Calcular</button></form>
<?php foreach($errors as $error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
<div class="stats finance-stats"><div class="stat"><strong><?=e($money($passiveTotals['billed']))?></strong><span>Facturación del equipo</span></div><div class="stat"><strong><?=e($money($passiveTotals['cost']))?></strong><span>Costo del equipo</span></div><div class="stat profit-stat <?=$passiveTotals['profit']<0?'is-negative':''?>"><strong><?=e($money($passiveTotals['profit']))?></strong><span>Ganancia pasiva</span></div><div class="stat profit-stat"><strong><?=e($money($ownerTotals['billed']))?></strong><span>Trabajo del OWNER</span></div><div class="stat total-earned-stat <?=$totalEarned<0?'is-negative':''?>"><strong><?=e($money($totalEarned))?></strong><span>Ganancia total del OWNER</span><small>Ganancia pasiva + trabajo propio</small></div></div>
<?php if($passiveClients===[]&&$ownerClients===[]):?><section class="panel empty-state"><h2>No hay horas en este período</h2><p>Probá con otro rango de fechas o verificá que el equipo haya cargado horas.</p></section><?php else:?>
<div class="page-heading profitability-section-heading"><div><h2>Ganancias pasivas</h2><p class="muted">Margen generado por el trabajo de los integrantes del equipo.</p></div><strong class="section-total"><?=e($money($passiveTotals['profit']))?></strong></div>
<?php if($passiveClients===[]):?><section class="panel empty-state"><p>No hay trabajo del equipo en este período.</p></section><?php else:?><div class="profitability-list">
<?php foreach($passiveClients as $client):$profit=(float)$client['billed']-(float)$client['cost'];?><section class="panel profitability-card"><header><div><h2 class="client-name" style="--client-color:<?=e($client['color'])?>"><i></i><?=e($client['name'])?></h2><small><?=e(formatMinutes((int)$client['minutes']))?> · <?=e($money($averageRate($client)))?>/h <span>facturados</span></small></div><div class="client-profit <?=$profit<0?'is-negative':''?>"><small>Ganancia pasiva</small><strong><?=e($money($profit))?></strong></div></header>
<div class="profit-metrics"><div><span>Facturación</span><strong><?=e($money((float)$client['billed']))?></strong></div><div><span>Costo del equipo</span><strong><?=e($money((float)$client['cost']))?></strong></div><div><span>Margen</span><strong><?=(float)$client['billed']>0?e(number_format($profit/(float)$client['billed']*100,1,',','.')).'%':'—'?></strong></div></div>
<div class="table-wrap"><table><thead><tr><th>Usuario</th><th>Horas</th><th>Pago por hora</th><th>Costo</th><th>Ganancia</th></tr></thead><tbody><?php foreach($client['users'] as $worker):?><tr><td><strong><?=e($worker['name'])?></strong></td><td><?=e(formatMinutes((int)$worker['minutes']))?></td><td><?=e($money((float)$worker['hourly_rate']))?>/h</td><td><strong><?=e($money((float)$worker['cost']))?></strong></td><td><strong class="<?=$worker['profit']<0?'is-negative':''?>"><?=e($money((float)$worker['profit']))?></strong></td></tr><?php endforeach;?></tbody></table></div></section><?php endforeach;?></div><?php endif;?>
<div class="page-heading profitability-section-heading"><div><h2>Trabajo del OWNER</h2><p class="muted">Facturación correspondiente exclusivamente al trabajo realizado por el OWNER.</p></div><strong class="section-total"><?=e($money($ownerTotals['billed']))?></strong></div>
<?php if($ownerClients===[]):?><section class="panel empty-state"><p>El OWNER no registró horas en este período.</p></section><?php else:?><div class="profitability-list">
<?php foreach($ownerClients as $client):?><section class="panel profitability-card owner-work-card"><header><div><h2 class="client-name" style="--client-color:<?=e($client['color'])?>"><i></i><?=e($client['name'])?></h2><small><?=e(formatMinutes((int)$client['minutes']))?> · <?=e($money($averageRate($client)))?>/h <span>facturados</span></small></div><div class="client-profit"><small>Trabajo facturado</small><strong><?=e($money((float)$client['billed']))?></strong></div></header>
<div class="profit-metrics"><div><span>Facturación directa</span><strong><?=e($money((float)$client['billed']))?></strong></div><div><span>Horas del OWNER</span><strong><?=e(formatMinutes((int)$client['minutes']))?></strong></div></div></section><?php endforeach;?></div><?php endif;?>
<?php endif;?></main></body></html>
