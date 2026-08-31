<?php
declare(strict_types=1);

/** @var array{config: array{app: array{name: string}}, pdo: PDO} $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$pdo = $app['pdo'];

if (currentUser($pdo) !== null) {
    redirect('/dashboard.php');
}

$username = '';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();
    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!preg_match('/^[a-z0-9._-]{3,50}$/', $username) || $password === '') {
        $error = 'Ingresá un usuario y una contraseña válidos.';
    } else {
        $user = (new UserRepository($pdo))->findByUsername($username);
        $dummyHash = '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $validPassword = password_verify($password, $user === null ? $dummyHash : (string) $user['password_hash']);

        if ($user === null || !$validPassword || !(bool) $user['active']) {
            $error = 'El usuario o la contraseña no son correctos.';
        } else {
            loginUser((int) $user['id']);
            redirect('/dashboard.php');
        }
    }
}
$flashes = consumeFlashes();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | <?= e($app['config']['app']['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-page">
    <div class="login-language language-switcher" aria-label="Language selector"><button type="button" data-language="es">ES</button><button type="button" data-language="en">EN</button></div>
<main class="login-shell">
<section class="login-card">
    <div class="login-brand"><img src="/assets/img/nyanhours-logo.png" alt="NyanHours"></div>
    <h1>Iniciar sesión</h1>
    <p class="muted">Ingresá con tu cuenta para registrar tus horas.</p>
    <?php foreach ($flashes as $message): ?>
        <div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div>
    <?php endforeach; ?>
    <?php if ($error !== null): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/login.php">
        <?= csrfField() ?>
        <label for="username">Usuario</label>
        <input id="username" name="username" value="<?= e($username) ?>" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="username" required autofocus>
        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button type="submit">Ingresar</button>
    </form>
    </section>
    <aside class="login-art" aria-hidden="true"><div><span>TIME TO CREATE</span><strong>Track the work.<br>Keep the magic.</strong></div><img src="/assets/img/nyansei-mascot-hi.png" alt=""></aside>
    </main>
    <script src="/assets/js/i18n.js?v=17" defer></script>
</body>
</html>
