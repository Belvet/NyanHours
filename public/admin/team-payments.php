<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$admin = requireOwner($app['pdo']);
$dateFrom = trim((string) ($_GET['date_from'] ?? date('Y-m-01')));
$dateTo = trim((string) ($_GET['date_to'] ?? date('Y-m-d')));
$errors = [];
$validDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
if (!$validDate($dateFrom) || !$validDate($dateTo)) $errors[] = 'Ingresá un rango de fechas válido.';
elseif ($dateFrom > $dateTo) $errors[] = 'La fecha desde no puede ser posterior a la fecha hasta.';
$payments = $errors === [] ? (new ProfitabilityRepository($app['pdo']))->teamPaymentsBetween($dateFrom, $dateTo) : [];
$totalMinutes = 0;
$totalPay = 0.0;
foreach ($payments as $payment) {
    $totalMinutes += (int) $payment['total_minutes'];
    $totalPay += (float) $payment['total_pay'];
}
$money = static fn (float $amount): string => 'USD ' . number_format($amount, 2, ',', '.');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pagos al equipo | NyanHours</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="app-layout"><?php renderSidebar($admin); ?>
<main class="container"><div class="page-heading"><div><h1>Pagos al equipo</h1><p class="muted">Totales a pagar por empleado en el período seleccionado, sin importar el cliente.</p></div></div>
<form class="panel date-filter" method="get"><div><label for="date_from">Desde</label><input id="date_from" name="date_from" type="date" value="<?=e($dateFrom)?>" required></div><div><label for="date_to">Hasta</label><input id="date_to" name="date_to" type="date" value="<?=e($dateTo)?>" required></div><button type="submit">Calcular</button></form>
<?php foreach($errors as $error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
<div class="stats"><div class="stat"><strong><?=e(formatMinutes($totalMinutes))?></strong><span>Horas del equipo</span></div><div class="stat profit-stat"><strong><?=e($money($totalPay))?></strong><span>Total a pagar</span></div></div>
<?php if($payments===[]):?><section class="panel empty-state"><h2>No hay pagos en este período</h2><p>No se encontraron horas cargadas por empleados.</p></section><?php else:?><section class="panel table-wrap"><table><thead><tr><th>Empleado</th><th>Horas trabajadas</th><th>Tarifa por hora</th><th>Total a pagar</th></tr></thead><tbody>
<?php foreach($payments as $payment):?><tr><td><strong><?=e($payment['user_name'])?></strong></td><td><?=e(formatMinutes((int)$payment['total_minutes']))?></td><td><?=e($money((float)$payment['hourly_rate']))?>/h</td><td><strong><?=e($money((float)$payment['total_pay']))?></strong></td></tr><?php endforeach;?>
</tbody><tfoot><tr><th>Total</th><th><?=e(formatMinutes($totalMinutes))?></th><th>—</th><th><?=e($money($totalPay))?></th></tr></tfoot></table></section><?php endif;?>
</main></body></html>
