<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}
requireValidCsrf();
logoutUser();
redirect('/login.php');
