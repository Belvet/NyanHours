<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = requireAdmin($app['pdo']);
$repository = new ClientRepository($app['pdo']);
$name = '';
$color = '#5046E5';
$hourlyRate = '0.00';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $color = strtoupper(trim((string) ($_POST['color'] ?? '')));
    $hourlyRate = str_replace(',', '.', trim((string) ($_POST['hourly_rate'] ?? '')));
    if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Ingresá un nombre de hasta 150 caracteres.';
    if ($name !== '' && $repository->findByName($name) !== null) $errors[] = 'Ya existe un cliente con ese nombre.';
    if (!preg_match('/^#[0-9A-F]{6}$/', $color)) $errors[] = 'Seleccioná un color válido.';
    if (!is_numeric($hourlyRate) || (float) $hourlyRate < 0) $errors[] = 'La tarifa facturada debe ser un número igual o mayor que cero.';
    if ($errors === []) {
        $repository->create($name, $color, (float) $hourlyRate);
        flash('success', 'Cliente creado correctamente.');
        redirect('/admin/clients/');
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Nuevo cliente | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><a class="button-secondary" href="/admin/clients/">Volver</a></header>
<main class="container narrow"><h1>Nuevo cliente</h1><p class="muted">El cliente quedará disponible inmediatamente para todo el equipo.</p>
<?php if ($errors !== []): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="panel" method="post"><?= csrfField() ?><label for="name">Nombre del cliente</label><input id="name" name="name" value="<?= e($name) ?>" maxlength="150" required autofocus><label for="hourly_rate">Tarifa facturada por hora (USD)</label><input id="hourly_rate" name="hourly_rate" type="number" min="0" step="0.01" value="<?=e($hourlyRate)?>" required><small>Importe que se le cobra al cliente por cada hora trabajada.</small><label for="color">Color identificador</label><div class="color-field"><input id="color" name="color" type="color" value="<?= e(strtolower($color)) ?>" required><span>Se usará en toda la aplicación.</span></div><div class="actions form-actions"><button type="submit">Crear cliente</button><a class="button-secondary" href="/admin/clients/">Cancelar</a></div></form>
</main></body></html>
