<?php
declare(strict_types=1);
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$owner = requireOwner($app['pdo']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
requireValidCsrf();
$newOwnerId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!is_int($newOwnerId)) { flash('error','Seleccioná un usuario válido.'); redirect('/admin/users/'); }
try {
    (new UserRepository($app['pdo']))->transferOwnership((int)$owner['id'], $newOwnerId);
    flash('success','OWNER transferido correctamente. Tu cuenta ahora es ADMIN con pago por hora de USD 0.');
} catch (DomainException $exception) {
    flash('error',$exception->getMessage());
}
redirect('/admin/users/');
