<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = requireAdmin($app['pdo']);
$repository = new UserRepository($app['pdo']);
$values = ['name' => '', 'username' => '', 'role' => 'operator', 'hourly_rate' => '0.00'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $values = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'username' => strtolower(trim((string) ($_POST['username'] ?? ''))),
        'role' => (string) ($_POST['role'] ?? ''),
        'hourly_rate' => str_replace(',', '.', trim((string) ($_POST['hourly_rate'] ?? ''))),
    ];
    $password = (string) ($_POST['password'] ?? '');
    $allowedRoles = ['admin','operator'];
    if ($values['name'] === '' || mb_strlen($values['name']) > 100) $errors[] = 'Ingresá un nombre de hasta 100 caracteres.';
    if (!preg_match('/^[a-z0-9._-]{3,50}$/', $values['username'])) $errors[] = 'El usuario debe tener entre 3 y 50 caracteres y usar solo letras, números, punto, guion o guion bajo.';
    if (!in_array($values['role'], $allowedRoles, true)) $errors[] = 'Seleccioná un rol válido.';
    if ($values['role'] === 'owner') $values['hourly_rate'] = '0.00';
    if (!is_numeric($values['hourly_rate']) || (float) $values['hourly_rate'] < 0) $errors[] = 'La tarifa debe ser un número igual o mayor que cero.';
    if (strlen($password) < 12) $errors[] = 'La contraseña debe tener al menos 12 caracteres.';
    if ($repository->findByUsername($values['username']) !== null) $errors[] = 'Ya existe una cuenta con ese nombre de usuario.';

    if ($errors === []) {
        $repository->create($values['name'], $values['username'], $password, $values['role'], (float) $values['hourly_rate']);
        flash('success', 'Usuario creado correctamente.');
        redirect('/admin/users/');
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Nuevo usuario | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><a class="button-secondary" href="/admin/users/">Volver</a></header>
<main class="container narrow"><h1>Nuevo usuario</h1><p class="muted">OWNER controla la rentabilidad, ADMIN gestiona la aplicación y OPERADOR registra sus horas.</p>
<?php if ($errors !== []): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="panel form-grid" method="post"><?= csrfField() ?>
    <div class="field-full"><label for="name">Nombre</label><input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="100" required></div>
    <div class="field-full"><label for="username">Usuario</label><input id="username" name="username" value="<?= e($values['username']) ?>" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="off" required><small>Se utilizará para iniciar sesión. No hace falta que sea un email.</small></div>
    <div><label for="role">Rol</label><select id="role" name="role" required><option value="operator" <?= $values['role'] === 'operator' ? 'selected' : '' ?>>OPERADOR</option><option value="admin" <?= $values['role'] === 'admin' ? 'selected' : '' ?>>ADMIN</option></select><small>Solo puede existir una cuenta OWNER.</small></div>
    <div><label for="hourly_rate">Pago por hora (USD)</label><input id="hourly_rate" name="hourly_rate" type="number" min="0" step="0.01" value="<?= e($values['hourly_rate']) ?>" required><small>Para OWNER el costo se establece automáticamente en cero.</small></div>
    <div class="field-full"><label for="password">Contraseña inicial</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required><small>Mínimo 12 caracteres.</small></div>
    <div class="field-full actions"><button type="submit">Crear usuario</button><a class="button-secondary" href="/admin/users/">Cancelar</a></div>
</form></main></body></html>
