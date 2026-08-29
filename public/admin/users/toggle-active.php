<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$currentUser = requireAdmin($app['pdo']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
requireValidCsrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$repository = new UserRepository($app['pdo']);
$user = is_int($id) ? $repository->findById($id) : null;
if ($user === null) { http_response_code(404); exit('Usuario no encontrado.'); }
if ($user['role'] === 'owner' && $currentUser['role'] !== 'owner') { http_response_code(403); exit('Solo OWNER puede modificar una cuenta OWNER.'); }
if ($id === (int) $currentUser['id']) { flash('error', 'No podés desactivar tu propia cuenta.'); redirect('/admin/users/'); }
$newActive = !(bool) $user['active'];
if (!$newActive && $user['role'] === 'admin' && $repository->countActiveAdmins() <= 1) {
    flash('error', 'Debe quedar al menos un administrador activo.');
    redirect('/admin/users/');
}
if (!$newActive && $user['role'] === 'owner' && $repository->countActiveOwners() <= 1) {
    flash('error', 'Debe quedar al menos un OWNER activo.');
    redirect('/admin/users/');
}
$repository->setActive($id, $newActive);
flash('success', $newActive ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.');
redirect('/admin/users/');
