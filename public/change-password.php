<?php
declare(strict_types=1);
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$user = requireLogin($app['pdo']);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    if ($currentPassword === '') $errors[] = 'Ingresá tu contraseña actual.';
    if (strlen($newPassword) < 12) $errors[] = 'La nueva contraseña debe tener al menos 12 caracteres.';
    if ($newPassword !== $confirmation) $errors[] = 'La confirmación no coincide con la nueva contraseña.';
    if ($currentPassword !== '' && hash_equals($currentPassword, $newPassword)) $errors[] = 'La nueva contraseña debe ser diferente de la actual.';
    if ($errors === []) {
        if (!(new UserRepository($app['pdo']))->changeOwnPassword((int)$user['id'], $currentPassword, $newPassword)) {
            $errors[] = 'La contraseña actual no es correcta.';
        } else {
            flash('success', 'Contraseña actualizada correctamente.');
            redirect('/change-password.php');
        }
    }
}
$flashes = consumeFlashes();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cambiar contraseña | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user);?>
<main class="container narrow"><div class="page-heading"><div><h1>Cambiar contraseña</h1><p class="muted">Cambiá la contraseña de tu cuenta.</p></div></div>
<?php foreach($flashes as $message):?><div class="alert alert-<?=e($message['type'])?>"><?=e($message['message'])?></div><?php endforeach;?>
<?php if($errors!==[]):?><div class="alert alert-error"><ul><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<form class="panel form-grid" method="post"><?=csrfField()?>
<div class="field-full"><label for="current_password">Contraseña actual</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
<div class="field-full"><label for="new_password">Nueva contraseña</label><input id="new_password" name="new_password" type="password" minlength="12" autocomplete="new-password" required><small>Mínimo 12 caracteres.</small></div>
<div class="field-full"><label for="password_confirmation">Repetir nueva contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
<div class="field-full actions"><button type="submit">Cambiar contraseña</button></div>
</form></main></body></html>
