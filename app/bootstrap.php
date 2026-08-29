<?php

declare(strict_types=1);

$configFile = dirname(__DIR__) . '/config/config.local.php';

if (!is_file($configFile)) {
    throw new RuntimeException(
        'Falta config/config.local.php. Copiá config/config.example.php y completá los datos locales.'
    );
}

/** @var array{app: array{name: string, environment: string, timezone: string}, database: array{host: string, port: int, name: string, user: string, password: string, charset: string}} $config */
$config = require $configFile;

date_default_timezone_set($config['app']['timezone']);

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_name('nyanhours_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/ClientRepository.php';
require_once __DIR__ . '/repositories/TimeEntryRepository.php';
require_once __DIR__ . '/repositories/ProfitabilityRepository.php';
require_once __DIR__ . '/auth.php';

$database = $config['database'];
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $database['host'],
    $database['port'],
    $database['name'],
    $database['charset']
);

$pdo = new PDO($dsn, $database['user'], $database['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

return [
    'config' => $config,
    'pdo' => $pdo,
];
