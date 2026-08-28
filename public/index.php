<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
redirect(currentUser($app['pdo']) === null ? '/login.php' : '/dashboard.php');
