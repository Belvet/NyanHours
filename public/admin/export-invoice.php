<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
requireAdmin($app['pdo']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
requireValidCsrf();
require_once dirname(__DIR__, 2) . '/app/vendor/fpdf/fpdf.php';

$clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
$client = is_int($clientId) ? (new ClientRepository($app['pdo']))->findById($clientId) : null;
if ($client === null) {
    http_response_code(404);
    exit('Client not found.');
}

$text = static fn (string $key, int $max): string => mb_substr(trim((string)($_POST[$key] ?? '')), 0, $max);
$validDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
$billTo = $text('bill_to', 150);
$billToEmail = $text('bill_to_email', 190);
$fromName = $text('from_name', 150);
$fromEmail = $text('from_email', 190);
$invoiceNumber = $text('invoice_number', 30);
$issueDate = $text('issue_date', 10);
$dateFrom = $text('date_from', 10);
$dateTo = $text('date_to', 10);
$currency = ($_POST['currency'] ?? '') === 'EUR' ? 'EUR' : 'USD';
$rate = filter_var($_POST['invoice_rate'] ?? null, FILTER_VALIDATE_FLOAT);
$accountOwner = $text('account_owner', 150);
$paymentDetails = $text('payment_details', 1200);
$descriptions = is_array($_POST['item_description'] ?? null) ? $_POST['item_description'] : [];
$hours = is_array($_POST['item_hours'] ?? null) ? $_POST['item_hours'] : [];

if ($billTo === '' || ($billToEmail !== '' && !filter_var($billToEmail, FILTER_VALIDATE_EMAIL)) || $fromName === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || $invoiceNumber === '' ||
    !$validDate($issueDate) || !$validDate($dateFrom) || !$validDate($dateTo) || $dateFrom > $dateTo ||
    $rate === false || $rate < 0 || $accountOwner === '' || $paymentDetails === '') {
    http_response_code(422);
    exit('Please complete all invoice fields with valid information.');
}

$items = [];
foreach ($descriptions as $index => $description) {
    if (count($items) >= 40) break;
    $description = mb_substr(trim((string)$description), 0, 180);
    $itemHours = filter_var($hours[$index] ?? null, FILTER_VALIDATE_FLOAT);
    if ($description === '' || $itemHours === false || $itemHours <= 0 || $itemHours > 10000) continue;
    $items[] = ['description' => $description, 'hours' => (float)$itemHours, 'amount' => (float)$itemHours * (float)$rate];
}
if ($items === []) {
    http_response_code(422);
    exit('Add at least one valid invoice item.');
}

$pdfText = static function (string $value): string {
    $converted = iconv('UTF-8', 'windows-1252//TRANSLIT', $value);
    return $converted === false ? $value : $converted;
};
$formatDate = static fn (string $date): string => date('m/d/Y', strtotime($date));
$symbol = $currency === 'EUR' ? '€' : '$';
$formatMoney = static fn (float $amount): string => $symbol . number_format($amount, 2, '.', ',');
$formatHours = static function (float $value): string {
    $formatted = number_format($value, 2, '.', '');
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    return $formatted . ($value === 1.0 ? ' hour' : ' hours');
};
$total = array_sum(array_column($items, 'amount'));

final class NyanHoursInvoicePdf extends FPDF
{
    public string $logoPath = '';
    public string $currency = 'USD';

    public function Header(): void
    {
        if (is_file($this->logoPath)) $this->Image($this->logoPath, 150, 13, 43);
        $this->SetFillColor(93, 86, 173);
        $this->Rect(0, 0, 8, 297, 'F');
    }

    public function Footer(): void
    {
        $this->SetY(-13);
        $this->SetTextColor(130, 126, 140);
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(0, 5, 'Nyansei Studio  |  Invoice  |  ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }

    public function sectionLabel(string $label): void
    {
        $this->SetTextColor(93, 86, 173);
        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell(0, 6, strtoupper($label), 0, 1);
    }

    public function itemsHeader(): void
    {
        $this->SetFillColor(93, 86, 173);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell(112, 9, 'INVOICE ITEMS', 0, 0, 'L', true);
        $this->Cell(35, 9, 'QUANTITY', 0, 0, 'R', true);
        $this->Cell(38, 9, 'AMOUNT', 0, 1, 'R', true);
    }

    public function itemRow(string $description, string $quantity, string $amount, bool $alternate): void
    {
        if ($this->GetY() + 12 > 266) {
            $this->AddPage();
            $this->SetY(28);
            $this->itemsHeader();
        }
        if ($alternate) $this->SetFillColor(248, 246, 252);
        else $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(41, 40, 45);
        $this->SetDrawColor(226, 222, 235);
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(112, 11, $this->fitText($description, 106), 'B', 0, 'L', true);
        $this->Cell(35, 11, $quantity, 'B', 0, 'R', true);
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(38, 11, $amount, 'B', 1, 'R', true);
    }

    private function fitText(string $text, float $width): string
    {
        if ($this->GetStringWidth($text) <= $width) return $text;
        $suffix = '...';
        while ($text !== '' && $this->GetStringWidth($text . $suffix) > $width) {
            $text = substr($text, 0, -1);
        }
        return rtrim($text) . $suffix;
    }
}

$pdf = new NyanHoursInvoicePdf('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(17, 13, 8);
$pdf->SetAutoPageBreak(true, 18);
$pdf->logoPath = dirname(__DIR__) . '/assets/img/nyansei-logo.png';
$pdf->currency = $currency;
$pdf->SetTitle($pdfText('Invoice ' . $invoiceNumber . ' - ' . $billTo));
$pdf->SetAuthor($pdfText($fromName));
$pdf->AddPage();

$pdf->SetXY(17, 18);
$pdf->SetTextColor(41, 40, 45);
$pdf->SetFont('Helvetica', 'B', 27);
$pdf->Cell(95, 12, 'INVOICE', 0, 1);
$pdf->SetX(17);
$pdf->SetTextColor(93, 86, 173);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->Cell(95, 7, '# ' . $pdfText($invoiceNumber), 0, 1);

$pdf->SetY(49);
$pdf->sectionLabel('Bill to');
$pdf->SetTextColor(41, 40, 45);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(88, 7, $pdfText($billTo), 0, 0);
$pdf->SetX(112);
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->Cell(35, 7, 'DATE ISSUED', 0, 0);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(45, 7, $formatDate($issueDate), 0, 1, 'R');
$pdf->SetX(112);
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->Cell(35, 7, 'INVOICE PERIOD', 0, 0);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(45, 7, $formatDate($dateFrom) . ' - ' . $formatDate($dateTo), 0, 1, 'R');
if ($billToEmail !== '') {
    $pdf->SetXY(17, 63);
    $pdf->SetTextColor(64, 105, 190);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(88, 6, $pdfText($billToEmail), 0, 1);
}

$pdf->SetY(76);
$pdf->sectionLabel('From');
$pdf->SetTextColor(41, 40, 45);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->Cell(0, 6, $pdfText($fromName), 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(64, 105, 190);
$pdf->Cell(0, 6, $pdfText($fromEmail), 0, 1);
$pdf->Ln(8);

$pdf->itemsHeader();
foreach ($items as $index => $item) {
    $pdf->itemRow($pdfText($item['description']), $formatHours($item['hours']), $pdfText($formatMoney($item['amount'])), $index % 2 === 1);
}
$pdf->Ln(5);
$summaryX = 126;
$pdf->SetX($summaryX);
$pdf->SetTextColor(93, 86, 173);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(28, 8, 'TOTAL', 0, 0);
$pdf->SetTextColor(41, 40, 45);
$pdf->Cell(38, 8, $pdfText($formatMoney($total)), 0, 1, 'R');
$pdf->SetX($summaryX);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(28, 8, 'PAYMENT', 0, 0);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(38, 8, $currency, 0, 1, 'R');
$pdf->SetX($summaryX);
$pdf->SetFillColor(239, 236, 252);
$pdf->SetTextColor(93, 86, 173);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(28, 10, 'BALANCE', 0, 0, 'L', true);
$pdf->Cell(38, 10, $pdfText($formatMoney($total)), 0, 1, 'R', true);

if ($pdf->GetY() + 48 > 274) $pdf->AddPage();
$pdf->Ln(9);
$pdf->sectionLabel('Payment details');
$pdf->SetTextColor(41, 40, 45);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 7, $pdfText('Account owner: ' . $accountOwner), 0, 1);
$pdf->Ln(1);
foreach (preg_split('/\R/u', $paymentDetails) ?: [] as $line) {
    $pdf->MultiCell(0, 6, $pdfText(trim($line)), 0, 'L');
}

$safeClient = preg_replace('/[^A-Za-z0-9_-]+/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', (string)$client['name']) ?: 'client');
$safeNumber = preg_replace('/[^A-Za-z0-9_-]+/', '_', $invoiceNumber) ?: 'invoice';
$pdf->Output('D', 'invoice_' . $safeNumber . '_' . trim((string)$safeClient, '_') . '.pdf');
