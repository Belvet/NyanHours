<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name = $argv[3] ?? 'Administrador';

if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || strlen($password) < 12) {
    fwrite(STDERR, "Uso: php database/seed-admin.php email contraseña-de-12-caracteres [nombre]\n");
    exit(1);
}

$statement = $app['pdo']->prepare(
    "INSERT INTO users (name, email, password_hash, role, hourly_rate, active)
     VALUES (:name, :email, :password_hash, 'admin', 0, 1)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = 'admin', active = 1"
);
$statement->execute([
    'name' => $name,
    'email' => strtolower($email),
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);
fwrite(STDOUT, "Administrador creado o actualizado correctamente.\n");
