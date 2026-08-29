<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$currentUser = requireAdmin($app['pdo']);
$users = (new UserRepository($app['pdo']))->all();
$flashes = consumeFlashes();
?>
<!doctype html>
<html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css">
</head><body class="app-layout"><?php renderSidebar($currentUser); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a></header>
<main class="container">
    <div class="page-heading"><div><h1>Usuarios</h1><p class="muted">Administrá accesos, roles y tarifas.</p></div><a class="button" href="/admin/users/create.php">Nuevo usuario</a></div>
    <?php foreach ($flashes as $message): ?><div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div><?php endforeach; ?>
    <section class="panel table-wrap">
        <table><thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Pago/hora</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= e($user['name']) ?><?= (int) $user['id'] === (int) $currentUser['id'] ? ' (vos)' : '' ?></td>
                <td><?= e($user['username']) ?></td>
                <td><span class="badge"><?= e(strtoupper((string)$user['role'])) ?></span></td>
                <td><?= $user['role']==='owner' ? 'Sin costo' : 'USD '.e(number_format((float)$user['hourly_rate'],2,',','.')).'/h' ?></td>
                <td><span class="status <?= (bool) $user['active'] ? 'status-active' : 'status-inactive' ?>"><?= (bool) $user['active'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="actions">
                    <?php if ($user['role'] !== 'owner' || $currentUser['role'] === 'owner'): ?>
                    <a class="button-secondary" href="/admin/users/edit.php?id=<?= e($user['id']) ?>">Editar</a>
                    <?php if ((int) $user['id'] !== (int) $currentUser['id']): ?>
                    <form method="post" action="/admin/users/toggle-active.php">
                        <?= csrfField() ?><input type="hidden" name="id" value="<?= e($user['id']) ?>">
                        <button class="button-secondary" type="submit"><?= (bool) $user['active'] ? 'Desactivar' : 'Activar' ?></button>
                    </form>
                    <?php endif; ?>
                    <?php else: ?><span class="muted">Solo OWNER</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </section>
</main></body></html>
