<?php
declare(strict_types=1);

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consumeFlashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function formatMinutes(int $minutes): string
{
    return sprintf('%d:%02d hs', intdiv($minutes, 60), $minutes % 60);
}

function durationInput(int $minutes): string
{
    return $minutes === 0 ? '' : sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
}

function parseDuration(string $value): ?int
{
    $value = trim(str_replace(',', '.', $value));
    if ($value === '') return 0;
    if (preg_match('/^(\d{1,2}):([0-5]?\d)(?::[0-5]\d)?$/', $value, $matches)) {
        $minutePart = (int) $matches[2];
        if (strlen($matches[2]) === 1) $minutePart *= 10;
        $minutes = ((int) $matches[1] * 60) + $minutePart;
    } elseif (preg_match('/^\d{3,4}$/', $value)) {
        $hourPart = (int) substr($value, 0, -2);
        $minutePart = (int) substr($value, -2);
        if ($hourPart > 24 || $minutePart > 59) return null;
        $minutes = ($hourPart * 60) + $minutePart;
    } elseif (is_numeric($value)) {
        $minutes = (int) round((float) $value * 60);
    } else {
        return null;
    }
    return $minutes >= 0 && $minutes <= 1440 ? $minutes : null;
}

function renderSidebar(array $user): void
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $links = [
        ['/dashboard.php', '⌂', 'Mi panel'],
        ['/time-tracker.php', '◷', 'Time Tracker'],
        ['/timesheet.php', '▦', 'Timesheet'],
    ];
    if (($user['role'] ?? '') === 'admin') {
        $links[] = ['/admin/', '◇', 'Administración'];
        $links[] = ['/admin/users/', '♙', 'Usuarios'];
        $links[] = ['/admin/clients/', '●', 'Clientes'];
        $links[] = ['/admin/reports.php', '▥', 'Reportes'];
    }
    ?>
    <aside class="app-sidebar">
        <a class="sidebar-logo" href="/dashboard.php" aria-label="NyanHours"><img src="/assets/img/nyansei-logo.png" alt="Nyansei Studio"><span>NyanHours</span></a>
        <nav class="sidebar-nav" aria-label="Navegación principal">
            <?php foreach ($links as [$href, $icon, $label]):
                $active = $href === '/admin/' ? $path === '/admin/' : ($href === '/dashboard.php' ? $path === $href : str_starts_with($path, $href));
            ?>
                <a href="<?= e($href) ?>" class="sidebar-link <?= $active ? 'active' : '' ?>">
                    <span class="sidebar-icon" aria-hidden="true"><?= e($icon) ?></span><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="language-switcher" aria-label="Language selector">
            <button type="button" data-language="es">ES</button><button type="button" data-language="en">EN</button>
        </div>
        <div class="sidebar-user">
            <div><strong><?= e($user['name'] ?? '') ?></strong><small><?= ($user['role'] ?? '') === 'admin' ? 'ADMIN' : 'OPERADOR' ?></small></div>
            <form method="post" action="/logout.php"><?= csrfField() ?><button type="submit">Salir</button></form>
        </div>
    </aside>
    <script src="/assets/js/i18n.js?v=2" defer></script>
    <?php
}
