<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = requireAdmin($app['pdo']);
$clients = (new ClientRepository($app['pdo']))->all();
$flashes = consumeFlashes();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Clientes | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a><nav class="actions"><a class="button" href="/admin/clients/create.php">Nuevo cliente</a></nav></header>
<main class="container"><div class="page-heading"><div><h1>Clientes</h1><p class="muted">Definí qué clientes estarán disponibles al registrar horas.</p></div><a class="button" href="/admin/clients/create.php">Nuevo cliente</a></div>
<?php foreach ($flashes as $message): ?><div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div><?php endforeach; ?>
<section class="panel table-wrap">
<?php if ($clients === []): ?><div class="empty-state"><h2>Todavía no hay clientes</h2><p>Creá el primero para comenzar a registrar horas.</p><a class="button" href="/admin/clients/create.php">Crear cliente</a></div>
<?php else: ?><table><thead><tr><th>Cliente</th><th>Tarifa facturada</th><th>Estado</th><th>Creado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($clients as $client): ?><tr>
    <td><strong class="client-name" style="--client-color: <?= e($client['color']) ?>"><i></i><?= e($client['name']) ?></strong></td>
    <td><strong>USD <?=e(number_format((float)$client['hourly_rate'],2,',','.'))?>/h</strong></td>
    <td><span class="status <?= (bool) $client['active'] ? 'status-active' : 'status-inactive' ?>"><?= (bool) $client['active'] ? 'Activo' : 'Inactivo' ?></span></td>
    <td><?= e(date('d/m/Y', strtotime((string) $client['created_at']))) ?></td>
    <td class="actions"><a class="button-secondary" href="/admin/clients/edit.php?id=<?= e($client['id']) ?>">Editar</a>
        <form method="post" action="/admin/clients/toggle-active.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($client['id']) ?>"><button class="button-secondary" type="submit"><?= (bool) $client['active'] ? 'Desactivar' : 'Activar' ?></button></form>
        <form method="post" action="/admin/clients/delete.php" onsubmit="return confirm('¿Eliminar definitivamente a <?=e($client['name'])?> y TODOS sus registros de horas? Esta acción no se puede deshacer.')"><?=csrfField()?><input type="hidden" name="id" value="<?=e($client['id'])?>"><button class="button-danger" type="submit">Eliminar definitivamente</button></form>
    </td>
</tr><?php endforeach; ?>
</tbody></table><?php endif; ?>
</section></main></body></html>
