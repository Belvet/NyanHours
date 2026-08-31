<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'NyanHours',
        'environment' => 'local',
        'timezone' => 'America/Argentina/Buenos_Aires',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'nyan_hours',
        'user' => 'nyan_hours_user',
        'password' => 'CAMBIAR_ESTA_CLAVE',
        'charset' => 'utf8mb4',
    ],
    // Optional defaults for the invoice builder. Keep real banking details only
    // in config.local.php, which is excluded from Git.
    'invoice' => [
        'from_name' => 'Your name',
        'from_email' => 'you@example.com',
        'account_owner' => 'Your name',
        'usd_bank_details' => "Bank name and address:\nAccount number:\nRouting number:\nSwift/BIC:",
        'eur_bank_details' => "Bank name and address:\nIBAN:\nSwift/BIC:",
    ],
];
