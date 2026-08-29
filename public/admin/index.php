<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
requireAdmin($app['pdo']);
redirect('/admin/users/');
