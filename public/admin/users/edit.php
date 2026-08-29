<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$currentUser = requireAdmin($app['pdo']);
$repository = new UserRepository($app['pdo']);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user = is_int($id) ? $repository->findById($id) : null;
if ($user === null) { http_response_code(404); exit('Usuario no encontrado.'); }
if ($user['role'] === 'owner' && $currentUser['role'] !== 'owner') { http_response_code(403); exit('Solo OWNER puede editar una cuenta OWNER.'); }
$values = ['name' => (string) $user['name'], 'username' => (string) $user['username'], 'role' => (string) $user['role'], 'hourly_rate' => (string) $user['hourly_rate']];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $values = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'username' => strtolower(trim((string) ($_POST['username'] ?? ''))),
        'role' => (string) ($_POST['role'] ?? ''),
        'hourly_rate' => str_replace(',', '.', trim((string) ($_POST['hourly_rate'] ?? ''))),
    ];
    $password = trim((string) ($_POST['password'] ?? ''));
    $allowedRoles = $user['role'] === 'owner' ? ['owner','admin','operator'] : ['admin','operator'];
    if ($values['name'] === '' || mb_strlen($values['name']) > 100) $errors[] = 'Ingresá un nombre de hasta 100 caracteres.';
    if (!preg_match('/^[a-z0-9._-]{3,50}$/', $values['username'])) $errors[] = 'El usuario debe tener entre 3 y 50 caracteres y usar solo letras, números, punto, guion o guion bajo.';
    if (!in_array($values['role'], $allowedRoles, true)) $errors[] = 'Seleccioná un rol válido.';
    if ($values['role'] === 'owner') $values['hourly_rate'] = '0.00';
    if (!is_numeric($values['hourly_rate']) || (float) $values['hourly_rate'] < 0) $errors[] = 'La tarifa debe ser un número igual o mayor que cero.';
    if ($password !== '' && strlen($password) < 12) $errors[] = 'La nueva contraseña debe tener al menos 12 caracteres.';
    $usernameOwner = $repository->findByUsername($values['username']);
    if ($usernameOwner !== null && (int) $usernameOwner['id'] !== $id) $errors[] = 'Ya existe otra cuenta con ese nombre de usuario.';
    if ($id === (int) $currentUser['id'] && $values['role'] !== $currentUser['role']) $errors[] = 'No podés cambiar tu propio rol.';
    if ($user['role'] === 'admin' && $values['role'] !== 'admin' && $repository->countActiveAdmins() <= 1) $errors[] = 'Debe quedar al menos un administrador activo.';
    if ($user['role'] === 'owner' && $values['role'] !== 'owner' && $repository->countActiveOwners() <= 1) $errors[] = 'Debe quedar al menos un OWNER activo.';

    if ($errors === []) {
        $repository->update($id, $values['name'], $values['username'], $values['role'], (float) $values['hourly_rate'], $password === '' ? null : $password);
        flash('success', 'Usuario actualizado correctamente.');
        redirect('/admin/users/');
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Editar usuario | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($currentUser); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><a class="button-secondary" href="/admin/users/">Volver</a></header>
<main class="container narrow"><h1>Editar usuario</h1><p class="muted">Actualizá los datos de <?= e($user['name']) ?>.</p>
<?php if ($errors !== []): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="panel form-grid" method="post"><?= csrfField() ?>
    <div class="field-full"><label for="name">Nombre</label><input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="100" required></div>
    <div class="field-full"><label for="username">Usuario</label><input id="username" name="username" value="<?= e($values['username']) ?>" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" required></div>
    <div><label for="role">Rol</label><select id="role" name="role" required <?= $id === (int) $currentUser['id'] ? 'disabled' : '' ?>><option value="operator" <?= $values['role'] === 'operator' ? 'selected' : '' ?>>OPERADOR</option><option value="admin" <?= $values['role'] === 'admin' ? 'selected' : '' ?>>ADMIN</option><?php if($user['role']==='owner'):?><option value="owner" <?= $values['role'] === 'owner' ? 'selected' : '' ?>>OWNER</option><?php endif;?></select><?php if ($id === (int) $currentUser['id']): ?><input type="hidden" name="role" value="<?=e($currentUser['role'])?>"><?php endif; ?><small>Solo puede existir una cuenta OWNER.</small></div>
    <div><label for="hourly_rate">Pago por hora (USD)</label><?php if($user['role']==='owner'):?><input value="Sin costo" disabled><input type="hidden" name="hourly_rate" value="0"><small>OWNER no representa un costo: su trabajo queda íntegramente como ganancia.</small><?php else:?><input id="hourly_rate" name="hourly_rate" type="number" min="0" step="0.01" value="<?= e($values['hourly_rate']) ?>" required><?php endif;?></div>
    <div class="field-full"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password"><small>Dejala vacía para conservar la contraseña actual.</small></div>
    <div class="field-full actions"><button type="submit">Guardar cambios</button><a class="button-secondary" href="/admin/users/">Cancelar</a></div>
</form>
<?php if($currentUser['role']==='owner' && $user['role']!=='owner' && (bool)$user['active']):?>
<section class="panel owner-transfer-panel"><h2>Transferir propiedad</h2><p>El usuario seleccionado se convertirá en el único OWNER de NyanHours. Tu cuenta pasará automáticamente a ADMIN con un pago por hora de USD 0.</p>
<form method="post" action="/admin/users/transfer-owner.php" onsubmit="return confirm('¿Estás seguro? El usuario seleccionado pasará a ser OWNER y tu cuenta pasará a ser ADMIN con un pago por hora de USD 0.');"><?=csrfField()?><input type="hidden" name="user_id" value="<?=e($user['id'])?>"><button class="button-danger" type="submit">Transferir rol OWNER</button></form></section>
<?php endif;?>
</main></body></html>
