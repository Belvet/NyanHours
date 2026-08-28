<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 3) . '/app/bootstrap.php';
requireAdmin($app['pdo']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
requireValidCsrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$repository = new ClientRepository($app['pdo']);
$client = is_int($id) ? $repository->findById($id) : null;
if ($client === null) { http_response_code(404); exit('Cliente no encontrado.'); }
$newActive = !(bool) $client['active'];
$repository->setActive($id, $newActive);
flash('success', $newActive ? 'Cliente activado correctamente.' : 'Cliente desactivado correctamente.');
redirect('/admin/clients/');
