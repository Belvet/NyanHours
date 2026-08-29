<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$username = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name = $argv[3] ?? 'Administrador';

if (!is_string($username) || !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username) || !is_string($password) || strlen($password) < 12) {
    fwrite(STDERR, "Uso: php database/seed-admin.php usuario contraseña-de-12-caracteres [nombre]\n");
    exit(1);
}

$statement = $app['pdo']->prepare(
    "INSERT INTO nh_users (name, username, password_hash, role, hourly_rate, active)
     VALUES (:name, :username, :password_hash, 'admin', 0, 1)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = 'admin', active = 1"
);
$statement->execute([
    'name' => $name,
    'username' => strtolower($username),
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);
fwrite(STDOUT, "Administrador creado o actualizado correctamente.\n");
