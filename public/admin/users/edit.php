<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$currentUser = requireAdmin($app['pdo']);
$repository = new UserRepository($app['pdo']);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user = is_int($id) ? $repository->findById($id) : null;
if ($user === null) { http_response_code(404); exit('Usuario no encontrado.'); }
$values = ['name' => (string) $user['name'], 'email' => (string) $user['email'], 'role' => (string) $user['role'], 'hourly_rate' => (string) $user['hourly_rate']];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $values = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
        'role' => (string) ($_POST['role'] ?? ''),
        'hourly_rate' => str_replace(',', '.', trim((string) ($_POST['hourly_rate'] ?? ''))),
    ];
    $password = trim((string) ($_POST['password'] ?? ''));
    if ($values['name'] === '' || mb_strlen($values['name']) > 100) $errors[] = 'Ingresá un nombre de hasta 100 caracteres.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresá un email válido.';
    if (!in_array($values['role'], ['admin', 'operator'], true)) $errors[] = 'Seleccioná un rol válido.';
    if (!is_numeric($values['hourly_rate']) || (float) $values['hourly_rate'] < 0) $errors[] = 'La tarifa debe ser un número igual o mayor que cero.';
    if ($password !== '' && strlen($password) < 12) $errors[] = 'La nueva contraseña debe tener al menos 12 caracteres.';
    $emailOwner = $repository->findByEmail($values['email']);
    if ($emailOwner !== null && (int) $emailOwner['id'] !== $id) $errors[] = 'Ya existe otro usuario con ese email.';
    if ($id === (int) $currentUser['id'] && $values['role'] !== 'admin') $errors[] = 'No podés quitarte tu propio rol de administrador.';
    if ($user['role'] === 'admin' && $values['role'] !== 'admin' && $repository->countActiveAdmins() <= 1) $errors[] = 'Debe quedar al menos un administrador activo.';

    if ($errors === []) {
        $repository->update($id, $values['name'], $values['email'], $values['role'], (float) $values['hourly_rate'], $password === '' ? null : $password);
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
    <div class="field-full"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= e($values['email']) ?>" required></div>
    <div><label for="role">Rol</label><select id="role" name="role" required <?= $id === (int) $currentUser['id'] ? 'disabled' : '' ?>><option value="operator" <?= $values['role'] === 'operator' ? 'selected' : '' ?>>OPERADOR</option><option value="admin" <?= $values['role'] === 'admin' ? 'selected' : '' ?>>ADMIN</option></select><?php if ($id === (int) $currentUser['id']): ?><input type="hidden" name="role" value="admin"><?php endif; ?></div>
    <div><label for="hourly_rate">Tarifa por hora</label><input id="hourly_rate" name="hourly_rate" type="number" min="0" step="0.01" value="<?= e($values['hourly_rate']) ?>" required></div>
    <div class="field-full"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password"><small>Dejala vacía para conservar la contraseña actual.</small></div>
    <div class="field-full actions"><button type="submit">Guardar cambios</button><a class="button-secondary" href="/admin/users/">Cancelar</a></div>
</form></main></body></html>
