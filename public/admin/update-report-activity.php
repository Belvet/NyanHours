<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
requireAdmin($app['pdo']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'message'=>'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
    http_response_code(419);
    echo json_encode(['ok'=>false,'message'=>'La sesión venció. Actualizá la página.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
$date = trim((string) ($_POST['work_date'] ?? ''));
$originalActivity = trim((string) ($_POST['original_activity'] ?? ''));
$newActivity = trim((string) ($_POST['new_activity'] ?? ''));
$validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!is_int($userId) || !is_int($clientId) || $validDate === false || $validDate->format('Y-m-d') !== $date
    || $originalActivity === '' || $newActivity === '' || mb_strlen($newActivity) > 1000) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'message'=>'La actividad debe tener entre 1 y 1000 caracteres.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$repository = new TimeEntryRepository($app['pdo']);
if ($repository->isPeriodClosed($date)) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'message'=>'El período está cerrado.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$updated = $repository->updateActivityGroupByAdmin($userId, $clientId, $date, $originalActivity, $newActivity);
if ($updated === 0) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'message'=>'La actividad ya no existe o fue modificada.'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['ok'=>true,'activity'=>$newActivity,'updated_entries'=>$updated], JSON_UNESCAPED_UNICODE);
