<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--confirm') {
    fwrite(STDERR, "Este script elimina todas las horas y las reemplaza por ejemplos. Ejecutalo con --confirm.\n");
    exit(1);
}
/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$pdo = $app['pdo'];
$examples = [
    ['User 2','Acme Studio','2026-08-24',120,'Content calendar planning'],
    ['User 2','Northstar Media','2026-08-25',90,'Social media asset design'],
    ['User 2','Greenfield Co.','2026-08-27',180,'Landing page wireframes'],
    ['User 3','Acme Studio','2026-08-24',150,'Newsletter design'],
    ['User 3','Northstar Media','2026-08-26',240,'Podcast episode editing'],
    ['User 3','Greenfield Co.','2026-08-28',300,'Website content updates'],
    ['User 1','Acme Studio','2026-08-25',60,'Client strategy meeting'],
    ['User 1','Northstar Media','2026-08-27',90,'Project planning and review'],
    ['User 1','Greenfield Co.','2026-08-28',120,'Creative direction'],
];
$users = $pdo->query('SELECT id, name FROM nh_users')->fetchAll(PDO::FETCH_KEY_PAIR);
$users = array_flip($users);
$clients = $pdo->query('SELECT id, name FROM nh_clients')->fetchAll(PDO::FETCH_KEY_PAIR);
$clients = array_flip($clients);
foreach ($examples as [$userName,$clientName]) {
    if (!isset($users[$userName])) throw new RuntimeException("No existe el usuario $userName.");
    if (!isset($clients[$clientName])) throw new RuntimeException("No existe el cliente $clientName.");
}
$pdo->beginTransaction();
try {
    $deleted = $pdo->exec('DELETE FROM nh_time_entries');
    $repository = new TimeEntryRepository($pdo);
    foreach ($examples as [$userName,$clientName,$date,$minutes,$description]) {
        $repository->createTrackerEvent((int)$users[$userName],(int)$clients[$clientName],$date,$minutes,$description);
    }
    $pdo->commit();
    fwrite(STDOUT, "Se eliminaron $deleted registros y se crearon " . count($examples) . " tareas de ejemplo.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $exception;
}
