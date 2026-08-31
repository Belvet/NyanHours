<?php
declare(strict_types=1);

/** @var array{pdo: PDO, config: array} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = requireAdmin($app['pdo']);

$clientId = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);
$client = is_int($clientId) ? (new ClientRepository($app['pdo']))->findById($clientId) : null;
if ($client === null) {
    http_response_code(404);
    exit('Cliente no encontrado.');
}

$validDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
if (!$validDate($dateFrom) || !$validDate($dateTo) || $dateFrom > $dateTo) {
    $dateTo = date('Y-m-d');
    $dateFrom = date('Y-m-01');
}

$entries = (new TimeEntryRepository($app['pdo']))->reportForClient($clientId, $dateFrom, $dateTo);
$normalizeActivity = static function (string $activity): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower(trim($activity), 'UTF-8')) ?: $activity;
    $ascii = preg_replace('/[^a-z0-9]+/', ' ', strtolower($ascii)) ?? '';
    $tokens = array_values(array_filter(explode(' ', trim($ascii))));
    foreach ($tokens as &$token) {
        if (strlen($token) > 4 && str_ends_with($token, 'ies')) $token = substr($token, 0, -3) . 'y';
        elseif (strlen($token) > 3 && str_ends_with($token, 's') && !str_ends_with($token, 'ss')) $token = substr($token, 0, -1);
    }
    unset($token);
    return implode(' ', $tokens);
};
$activitiesAreSimilar = static function (string $left, string $right): bool {
    if ($left === $right) return true;
    $longest = max(strlen($left), strlen($right));
    return $longest >= 10 && levenshtein($left, $right) <= max(1, (int)floor($longest * 0.12));
};
$items = [];
foreach ($entries as $entry) {
    $activity = (string) $entry['activity'];
    $normalized = $normalizeActivity($activity);
    $matchingKey = null;
    foreach ($items as $key => $item) {
        if ($activitiesAreSimilar($normalized, $item['normalized'])) { $matchingKey = $key; break; }
    }
    if ($matchingKey === null) {
        $items[] = ['description' => $activity, 'normalized' => $normalized, 'minutes' => (int)$entry['total_minutes']];
    } else {
        $items[$matchingKey]['minutes'] += (int)$entry['total_minutes'];
    }
}
$invoiceProfile = is_array($app['config']['invoice'] ?? null) ? $app['config']['invoice'] : [];
$returnQuery = http_build_query(['date_from' => $dateFrom, 'date_to' => $dateTo]);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Crear invoice | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($user); ?>
<header class="topbar"><a class="brand" href="/dashboard.php">NyanHours</a></header>
<main class="container invoice-container">
<div class="page-heading"><div><h1>Crear invoice</h1><p class="muted">Prepará la factura de <?=e($client['name'])?> y descargala en PDF.</p></div><a class="button-secondary" href="/admin/reports.php?<?=e($returnQuery)?>">Volver a reportes</a></div>
<?php if ($items === []): ?><div class="alert alert-error">No hay horas registradas para este cliente en el período seleccionado.</div><?php endif; ?>
<form class="invoice-builder" method="post" action="/admin/export-invoice.php" target="_blank">
<?=csrfField()?>
<input type="hidden" name="client_id" value="<?=e($clientId)?>">
<section class="panel invoice-section"><div class="invoice-section-title"><span>1</span><div><h2>Invoice details</h2><p>Información principal que verá el cliente.</p></div></div>
<div class="form-grid invoice-form-grid">
<div><label for="bill_to">Bill to</label><input id="bill_to" name="bill_to" maxlength="150" value="<?=e($client['name'])?>" required></div>
<div><label for="bill_to_email">Client billing email</label><input id="bill_to_email" name="bill_to_email" type="email" maxlength="190" value="<?=e((string)($client['billing_email'] ?? ''))?>" placeholder="client@company.com"></div>
<div><label for="invoice_number">Invoice #</label><input id="invoice_number" name="invoice_number" maxlength="30" placeholder="e.g. 004" required></div>
<div><label for="from_name">From</label><input id="from_name" name="from_name" maxlength="150" value="<?=e((string)($invoiceProfile['from_name'] ?? ''))?>" required></div>
<div><label for="from_email">Email</label><input id="from_email" name="from_email" type="email" maxlength="190" value="<?=e((string)($invoiceProfile['from_email'] ?? ''))?>" required></div>
<div><label for="issue_date">Date issued</label><input id="issue_date" name="issue_date" type="date" value="<?=e(date('Y-m-d'))?>" required></div>
<div class="invoice-period-fields"><div><label for="date_from">Period from</label><input id="date_from" name="date_from" type="date" value="<?=e($dateFrom)?>" readonly required></div><div><label for="date_to">Period to</label><input id="date_to" name="date_to" type="date" value="<?=e($dateTo)?>" readonly required></div></div>
</div></section>

<section class="panel invoice-section"><div class="invoice-section-title"><span>2</span><div><h2>Currency and items</h2><p>Elegí USD o EUR y ajustá la tarifa si necesitás convertirla.</p></div></div>
<div class="invoice-rate-row"><div><label for="currency">Currency</label><select id="currency" name="currency"><option value="USD">USD - US Dollar</option><option value="EUR">EUR - Euro</option></select></div><div><label for="invoice_rate">Hourly rate in selected currency</label><input id="invoice_rate" name="invoice_rate" type="number" min="0" step="0.01" value="<?=e(number_format((float)$client['hourly_rate'], 2, '.', ''))?>" required></div></div>
<p class="invoice-merge-note">Las tareas con nombres casi iguales se agrupan automáticamente. Igual podés editar o separar los conceptos antes de exportar.</p><div class="invoice-items" id="invoice-items">
<div class="invoice-item invoice-item-header"><span>Invoice item</span><span>Hours</span><span>Amount</span><span></span></div>
<?php foreach($items as $item): $description=$item['description']; $minutes=$item['minutes'];?><div class="invoice-item"><input name="item_description[]" value="<?=e($description === 'No detallado' ? 'Not detailed' : $description)?>" maxlength="180" required><input class="invoice-hours" name="item_hours[]" type="number" min="0.01" step="0.01" value="<?=e(number_format($minutes / 60, 2, '.', ''))?>" required><output class="invoice-line-total">USD 0.00</output><button class="invoice-remove" type="button" aria-label="Remove item">×</button></div><?php endforeach;?>
</div>
<div class="invoice-items-footer"><button class="button-secondary" id="add-invoice-item" type="button">+ Add item</button><div><span>Total</span><strong id="invoice-total">USD 0.00</strong></div></div>
</section>

<section class="panel invoice-section"><div class="invoice-section-title"><span>3</span><div><h2>Payment details</h2><p>Los datos cambian automáticamente al elegir USD o EUR.</p></div></div>
<label for="account_owner">Account owner</label><input id="account_owner" name="account_owner" maxlength="150" value="<?=e((string)($invoiceProfile['account_owner'] ?? ''))?>" required>
<label for="payment_details">Bank and account details</label><textarea id="payment_details" name="payment_details" rows="6" maxlength="1200" required></textarea>
<input type="hidden" id="usd-payment-details" value="<?=e((string)($invoiceProfile['usd_bank_details'] ?? ''))?>"><input type="hidden" id="eur-payment-details" value="<?=e((string)($invoiceProfile['eur_bank_details'] ?? ''))?>">
</section>
<div class="invoice-submit"><p><strong>PDF language:</strong> English</p><button type="submit" <?=$items===[]?'disabled':''?>>Generate invoice PDF</button></div>
</form>
</main><script src="/assets/js/invoice.js?v=1"></script></body></html>
