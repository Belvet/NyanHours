<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app=require dirname(__DIR__,2).'/app/bootstrap.php';
$user=requireLogin($app['pdo']); $repository=new TimeEntryRepository($app['pdo']);
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT); $entry=is_int($id)?$repository->findOwned($id,(int)$user['id']):null;
if($entry===null){http_response_code(404);exit('Actividad no encontrada.');}
$clients=(new ClientRepository($app['pdo']))->allActive();
$values=['description'=>(string)($entry['description']??''),'client_id'=>(string)$entry['client_id'],'work_date'=>(string)$entry['work_date'],'duration'=>durationInput((int)$entry['duration_minutes'])];
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    requireValidCsrf();
    $values=['description'=>trim((string)($_POST['description']??'')),'client_id'=>(string)($_POST['client_id']??''),'work_date'=>(string)($_POST['work_date']??''),'duration'=>(string)($_POST['duration']??'')];
    $clientId=filter_var($values['client_id'],FILTER_VALIDATE_INT); $client=is_int($clientId)?(new ClientRepository($app['pdo']))->findById($clientId):null;
    $minutes=parseDuration($values['duration']); $date=DateTimeImmutable::createFromFormat('!Y-m-d',$values['work_date']);
    if($values['description']===''||mb_strlen($values['description'])>1000)$errors[]='Escribí una actividad de hasta 1000 caracteres.';
    if($client===null||!(bool)$client['active'])$errors[]='Seleccioná un cliente activo.';
    if($minutes===null||$minutes<1)$errors[]='Ingresá una duración válida mayor que cero.';
    if($date===false||$date->format('Y-m-d')!==$values['work_date'])$errors[]='Ingresá una fecha válida.';
    elseif($repository->isPeriodClosed($values['work_date'])||$repository->isPeriodClosed((string)$entry['work_date']))$errors[]='No podés editar actividades de un período cerrado.';
    if($errors===[]){$repository->updateOwned($id,(int)$user['id'],$clientId,$values['work_date'],$minutes,$values['description']);flash('success','Actividad actualizada correctamente.');redirect('/time-tracker.php');}
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Editar actividad | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><a class="button-secondary" href="/time-tracker.php">Volver</a></header>
<main class="container narrow"><h1>Editar actividad</h1><?php if($errors!==[]):?><div class="alert alert-error"><ul><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="post" class="panel form-grid"><?=csrfField()?>
<div class="field-full"><label for="description">Actividad</label><input id="description" name="description" value="<?=e($values['description'])?>" maxlength="1000" required></div>
<div class="field-full"><label for="client_id">Cliente</label><select id="client_id" name="client_id" required><?php foreach($clients as $client):?><option value="<?=e($client['id'])?>" <?=(string)$client['id']===$values['client_id']?'selected':''?>><?=e($client['name'])?></option><?php endforeach;?></select></div>
<div><label for="work_date">Fecha</label><input id="work_date" name="work_date" type="date" value="<?=e($values['work_date'])?>" required></div>
<div><label for="duration">Duración</label><input id="duration" name="duration" value="<?=e($values['duration'])?>" inputmode="decimal" required></div>
<div class="field-full actions"><button type="submit">Guardar cambios</button><a class="button-secondary" href="/time-tracker.php">Cancelar</a></div>
</form></main></body></html>
