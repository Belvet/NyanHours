<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app=require dirname(__DIR__,2).'/app/bootstrap.php'; $user=requireLogin($app['pdo']);
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit('Método no permitido.');}
requireValidCsrf(); $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT); $repository=new TimeEntryRepository($app['pdo']);
$entry=is_int($id)?$repository->findOwned($id,(int)$user['id']):null; if($entry===null){http_response_code(404);exit('Actividad no encontrada.');}
if($repository->isPeriodClosed((string)$entry['work_date'])){flash('error','No podés eliminar una actividad de un período cerrado.');redirect('/time-tracker.php');}
$repository->deleteOwned($id,(int)$user['id']); flash('success','Actividad eliminada correctamente.'); redirect('/time-tracker.php');
