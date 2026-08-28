<?php
declare(strict_types=1);

function currentUser(PDO $pdo): ?array
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId)) {
        return null;
    }
    $user = (new UserRepository($pdo))->findActiveById($userId);
    if ($user === null) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function requireLogin(PDO $pdo): array
{
    $user = currentUser($pdo);
    if ($user === null) {
        flash('error', 'Iniciá sesión para continuar.');
        redirect('/login.php');
    }
    return $user;
}

function requireAdmin(PDO $pdo): array
{
    $user = requireLogin($pdo);
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('No tenés permisos para acceder a esta sección.');
    }
    return $user;
}

function loginUser(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}
