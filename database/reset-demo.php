<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--confirm') {
    fwrite(STDERR, "This script replaces the local demo data. Run it with --confirm.\n");
    exit(1);
}

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$pdo = $app['pdo'];
$passwords = [
    'User 1' => 'DemoOwner2026!',
    'User 2' => 'DemoAdmin2026!',
    'User 3' => 'DemoOperator2026!',
];

$pdo->beginTransaction();
try {
    $pdo->exec('DELETE FROM nh_time_entries');
    $pdo->exec('DELETE FROM nh_closed_periods');
    $pdo->exec('DELETE FROM nh_clients');
    $pdo->exec('DELETE FROM nh_users');

    $userStatement = $pdo->prepare(
        "INSERT INTO nh_users (name, username, password_hash, role, hourly_rate, active)
         VALUES (:name, :username, :hash, :role, :hourly_rate, 1)"
    );
    foreach ([
        ['User 1', 'user1', $passwords['User 1'], 'owner', 0],
        ['User 2', 'user2', $passwords['User 2'], 'admin', 25],
        ['User 3', 'user3', $passwords['User 3'], 'operator', 20],
    ] as [$name, $username, $password, $role, $hourlyRate]) {
        $userStatement->execute([
            'name' => $name,
            'username' => $username,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'hourly_rate' => $hourlyRate,
        ]);
    }

    $clientStatement = $pdo->prepare(
        'INSERT INTO nh_clients (name, color, hourly_rate, active) VALUES (:name, :color, :hourly_rate, 1)'
    );
    foreach ([
        ['Acme Studio', '#E85D75', 50],
        ['Northstar Media', '#2D9CDB', 55],
        ['Greenfield Co.', '#27AE60', 45],
    ] as [$name, $color, $hourlyRate]) {
        $clientStatement->execute(['name' => $name, 'color' => $color, 'hourly_rate' => $hourlyRate]);
    }

    $pdo->commit();
    fwrite(STDOUT, "Demo data created successfully.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $exception;
}
