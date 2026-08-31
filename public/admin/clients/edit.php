<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = requireAdmin($app['pdo']);
$repository = new ClientRepository($app['pdo']);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$client = is_int($id) ? $repository->findById($id) : null;
if ($client === null) { http_response_code(404); exit('Cliente no encontrado.'); }
$name = (string) $client['name'];
$billingEmail = (string) ($client['billing_email'] ?? '');
$color = (string) $client['color'];
$hourlyRate = (string) $client['hourly_rate'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $billingEmail = trim((string) ($_POST['billing_email'] ?? ''));
    $color = strtoupper(trim((string) ($_POST['color'] ?? '')));
    $hourlyRate = str_replace(',', '.', trim((string) ($_POST['hourly_rate'] ?? '')));
    if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Ingresá un nombre de hasta 150 caracteres.';
    if ($billingEmail !== '' && (mb_strlen($billingEmail) > 190 || !filter_var($billingEmail, FILTER_VALIDATE_EMAIL))) $errors[] = 'Ingresá un email de facturación válido.';
    $nameOwner = $name === '' ? null : $repository->findByName($name);
    if ($nameOwner !== null && (int) $nameOwner['id'] !== $id) $errors[] = 'Ya existe otro cliente con ese nombre.';
    if (!preg_match('/^#[0-9A-F]{6}$/', $color)) $errors[] = 'Seleccioná un color válido.';
    if (!is_numeric($hourlyRate) || (float) $hourlyRate < 0) $errors[] = 'La tarifa facturada debe ser un número igual o mayor que cero.';
    if ($errors === []) {
        $repository->update($id, $name, $billingEmail !== '' ? $billingEmail : null, $color, (float) $hourlyRate);
        flash('success', 'Cliente actualizado correctamente.');
        redirect('/admin/clients/');
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Editar cliente | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><a class="button-secondary" href="/admin/clients/">Volver</a></header>
<main class="container narrow"><h1>Editar cliente</h1><p class="muted">El nuevo nombre también aparecerá en sus registros históricos.</p>
<?php if ($errors !== []): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="panel" method="post"><?= csrfField() ?><label for="name">Nombre del cliente</label><input id="name" name="name" value="<?= e($name) ?>" maxlength="150" required autofocus><label for="billing_email">Email de facturación</label><input id="billing_email" name="billing_email" type="email" value="<?=e($billingEmail)?>" maxlength="190" placeholder="cliente@empresa.com"><small>Se precargará al crear invoices. Podés dejarlo vacío.</small><label for="hourly_rate">Tarifa facturada por hora (USD)</label><input id="hourly_rate" name="hourly_rate" type="number" min="0" step="0.01" value="<?=e($hourlyRate)?>" required><small>Importe que se le cobra al cliente por cada hora trabajada.</small><label for="color">Color identificador</label><div class="color-field"><input id="color" name="color" type="color" value="<?= e(strtolower($color)) ?>" required><span>Se usará en toda la aplicación.</span></div><div class="actions form-actions"><button type="submit">Guardar cambios</button><a class="button-secondary" href="/admin/clients/">Cancelar</a></div></form>
</main></body></html>
