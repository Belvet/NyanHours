<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--confirm') {
    fwrite(STDERR, "Este script reemplaza los datos locales. Ejecutalo con --confirm.\n");
    exit(1);
}
/** @var array{pdo: PDO} $app */
$app=require dirname(__DIR__).'/app/bootstrap.php'; $pdo=$app['pdo'];
$passwords = [
    'Pampli' => getenv('NYAN_PAMPLI_PASSWORD') ?: '',
    'Belvet' => getenv('NYAN_BELVET_PASSWORD') ?: '',
    'Toffi' => getenv('NYAN_TOFFI_PASSWORD') ?: '',
];
foreach ($passwords as $name => $password) {
    if (strlen($password) < 12) {
        fwrite(STDERR, "Falta NYAN_" . strtoupper($name) . "_PASSWORD (mínimo 12 caracteres).\n");
        exit(1);
    }
}
$pdo->beginTransaction();
try {
    $pdo->exec('DELETE FROM time_entries');
    $pdo->exec('DELETE FROM closed_periods');
    $pdo->exec('DELETE FROM clients');
    $pdo->exec('DELETE FROM users');
    $userStatement=$pdo->prepare("INSERT INTO users(name,email,password_hash,role,hourly_rate,active) VALUES(:name,:email,:hash,:role,0,1)");
    foreach ([
        ['Pampli','pampli@nyanhours.local',$passwords['Pampli'],'operator'],
        ['Belvet','belvet@nyanhours.local',$passwords['Belvet'],'operator'],
        ['Toffi','toffi@nyanhours.local',$passwords['Toffi'],'admin'],
    ] as [$name,$email,$password,$role]) $userStatement->execute(['name'=>$name,'email'=>$email,'hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>$role]);
    $clientStatement=$pdo->prepare('INSERT INTO clients(name,color,active) VALUES(:name,:color,1)');
    foreach([['Cinthya','#E85D75'],['Kelly','#2D9CDB'],['Erena','#27AE60']] as [$name,$color]) $clientStatement->execute(['name'=>$name,'color'=>$color]);
    $pdo->commit(); fwrite(STDOUT,"Datos locales reemplazados correctamente.\n");
} catch(Throwable $exception) { $pdo->rollBack(); throw $exception; }
