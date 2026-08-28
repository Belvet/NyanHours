<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
/** @var array{pdo: PDO} $app */
$app=require dirname(__DIR__,2).'/app/bootstrap.php'; $user=requireLogin($app['pdo']);
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['ok'=>false,'message'=>'Método no permitido.']);exit;}
$token=$_POST['csrf_token']??null;
if(!is_string($token)||!hash_equals(csrfToken(),$token)){http_response_code(419);echo json_encode(['ok'=>false,'message'=>'La sesión venció. Actualizá la página.']);exit;}
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT); $description=trim((string)($_POST['description']??''));
if(!is_int($id)||$description===''||mb_strlen($description)>1000){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'La actividad debe tener entre 1 y 1000 caracteres.']);exit;}
$repository=new TimeEntryRepository($app['pdo']); $entry=$repository->findOwned($id,(int)$user['id']);
if($entry===null){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'Actividad no encontrada.']);exit;}
if($repository->isPeriodClosed((string)$entry['work_date'])){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'El período está cerrado.']);exit;}
$repository->updateDescriptionOwned($id,(int)$user['id'],$description);
echo json_encode(['ok'=>true,'description'=>$description],JSON_UNESCAPED_UNICODE);
