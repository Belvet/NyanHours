<?php
declare(strict_types=1);

/** @var array{pdo: PDO} $app */
$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
requireAdmin($app['pdo']);
require_once dirname(__DIR__, 2) . '/app/vendor/fpdf/fpdf.php';

$clientId = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);
$client = is_int($clientId) ? (new ClientRepository($app['pdo']))->findById($clientId) : null;
if ($client === null) {
    http_response_code(404);
    exit('Cliente no encontrado.');
}
$language = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
$labels = $language === 'en'
    ? ['title'=>'Detailed Time Report','total'=>'Total Time','task'=>'TASK','duration'=>'DURATION','day'=>'DAY','subtotal'=>'Subtotal','empty'=>'Not detailed','filename'=>'detailed_report']
    : ['title'=>'Reporte detallado de horas','total'=>'Tiempo total','task'=>'TAREA','duration'=>'DURACIÓN','day'=>'DÍA','subtotal'=>'Subtotal','empty'=>'No detallado','filename'=>'reporte_detallado'];

$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$validDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
if (($dateFrom === '') !== ($dateTo === '') || ($dateFrom !== '' && (!$validDate($dateFrom) || !$validDate($dateTo) || $dateFrom > $dateTo))) {
    http_response_code(422);
    exit('El período indicado no es válido.');
}

$entries = (new TimeEntryRepository($app['pdo']))->reportForClient(
    $clientId,
    $dateFrom !== '' ? $dateFrom : null,
    $dateTo !== '' ? $dateTo : null
);
if ($entries === []) {
    http_response_code(404);
    exit('No hay horas registradas para exportar.');
}
if ($dateFrom === '') {
    $dateFrom = (string) $entries[0]['work_date'];
    $dateTo = (string) $entries[array_key_last($entries)]['work_date'];
}
$totalMinutes = array_sum(array_map(static fn (array $entry): int => (int) $entry['total_minutes'], $entries));

$pdfText = static function (string $value): string {
    $converted = iconv('UTF-8', 'windows-1252//TRANSLIT', $value);
    return $converted === false ? $value : $converted;
};
$formatPdfDuration = static fn (int $minutes): string => sprintf('%d h %02d min', intdiv($minutes, 60), $minutes % 60);
$formatPdfDate = static fn (string $date): string => date($language === 'en' ? 'm/d/Y' : 'd/m/Y', strtotime($date));

final class NyanHoursReportPdf extends FPDF
{
    public string $reportTitle = '';
    public string $period = '';
    public string $total = '';
    public string $logoPath = '';
    /** @var array<string,string> */
    public array $labels = [];

    public function Header(): void
    {
        $this->SetTextColor(41, 40, 45);
        $fontSize = 20;
        $this->SetFont('Helvetica', 'B', $fontSize);
        while ($fontSize > 13 && $this->GetStringWidth($this->reportTitle) > 122) {
            $this->SetFont('Helvetica', 'B', --$fontSize);
        }
        $this->SetXY(15, 18);
        $this->Cell(120, 9, $this->reportTitle, 0, 1);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetX(15);
        $this->Cell(120, 6, $this->period, 0, 1);
        if (is_file($this->logoPath)) $this->Image($this->logoPath, 149, 14, 45);
        $this->SetY(42);
        $this->SetFont('Helvetica', '', 11);
        $this->Cell(28, 7, $this->labels['total'], 0, 0);
        $this->SetFont('Helvetica', 'B', 12);
        $this->Cell(40, 7, $this->total, 0, 1);
        $this->Ln(8);
        $this->tableHeader();
    }

    public function Footer(): void
    {
        $this->SetY(-13);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(130, 126, 140);
        $this->Cell(0, 5, 'NyanHours  |  ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }

    public function tableHeader(): void
    {
        $this->SetFillColor(93, 86, 173);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(220, 216, 229);
        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell(155, 9, $this->labels['task'], 1, 0, 'L', true);
        $this->Cell(35, 9, $this->labels['duration'], 1, 1, 'R', true);
    }

    public function dayHeader(string $date, string $subtotal): void
    {
        if ($this->GetY() + 10 > 278) $this->AddPage();
        $this->SetFillColor(232, 228, 243);
        $this->SetTextColor(72, 65, 143);
        $this->SetDrawColor(205, 199, 221);
        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell(145, 9, $this->labels['day'] . ' ' . $date, 1, 0, 'L', true);
        $this->Cell(45, 9, $this->labels['subtotal'] . ': ' . $subtotal, 1, 1, 'R', true);
    }

    public function reportRow(string $task, string $duration, bool $alternate): void
    {
        $lineHeight = 5.5;
        $taskLines = $this->numberOfLines(151, $task);
        $rowHeight = max(10, $taskLines * $lineHeight + 3);
        if ($this->GetY() + $rowHeight > 278) $this->AddPage();
        $x = $this->GetX();
        $y = $this->GetY();
        if ($alternate) {
            $this->SetFillColor(248, 245, 251);
            $this->Rect($x, $y, 190, $rowHeight, 'F');
        }
        $this->SetDrawColor(220, 216, 229);
        $this->Rect($x, $y, 155, $rowHeight);
        $this->Rect($x + 155, $y, 35, $rowHeight);
        $this->SetTextColor(41, 40, 45);
        $this->SetFont('Helvetica', '', 9);
        $this->SetXY($x + 2, $y + 2.5);
        $this->MultiCell(151, $lineHeight, $task, 0, 'L');
        $this->SetXY($x + 157, $y + 2.5);
        $this->Cell(31, 5, $duration, 0, 0, 'R');
        $this->SetXY($x, $y + $rowHeight);
    }

    private function numberOfLines(float $width, string $text): int
    {
        $characterWidths = $this->CurrentFont['cw'];
        $maximumWidth = ($width - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $text = str_replace("\r", '', $text);
        $length = strlen($text);
        if ($length > 0 && $text[$length - 1] === "\n") $length--;
        $separator = -1;
        $index = 0;
        $lineStart = 0;
        $lineWidth = 0;
        $lines = 1;
        while ($index < $length) {
            $character = $text[$index];
            if ($character === "\n") {
                $index++;
                $separator = -1;
                $lineStart = $index;
                $lineWidth = 0;
                $lines++;
                continue;
            }
            if ($character === ' ') $separator = $index;
            $lineWidth += $characterWidths[$character] ?? 0;
            if ($lineWidth > $maximumWidth) {
                if ($separator === -1) {
                    if ($index === $lineStart) $index++;
                } else {
                    $index = $separator + 1;
                }
                $separator = -1;
                $lineStart = $index;
                $lineWidth = 0;
                $lines++;
            } else {
                $index++;
            }
        }
        return $lines;
    }
}

$pdf = new NyanHoursReportPdf('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(10, 12, 10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->labels = array_map($pdfText, $labels);
$pdf->reportTitle = $pdfText($labels['title'] . ' - ' . (string) $client['name']);
$pdf->period = $formatPdfDate($dateFrom) . ' - ' . $formatPdfDate($dateTo);
$pdf->total = $formatPdfDuration($totalMinutes);
$pdf->logoPath = dirname(__DIR__) . '/assets/img/nyansei-logo.png';
$pdf->SetTitle($pdf->reportTitle);
$pdf->SetAuthor('NyanHours');
$pdf->AddPage();
foreach (array_values(array_reduce($entries, static function (array $days, array $entry): array {
    $days[(string) $entry['work_date']][] = $entry;
    return $days;
}, [])) as $dayEntries) {
    $dayMinutes = array_sum(array_map(static fn (array $entry): int => (int) $entry['total_minutes'], $dayEntries));
    $pdf->dayHeader($formatPdfDate((string) $dayEntries[0]['work_date']), $formatPdfDuration($dayMinutes));
    foreach ($dayEntries as $index => $entry) {
        $pdf->reportRow(
            $pdfText((string) $entry['activity'] === 'No detallado' ? $labels['empty'] : (string) $entry['activity']),
            $formatPdfDuration((int) $entry['total_minutes']),
            $index % 2 === 1
        );
    }
}
$safeClient = preg_replace('/[^A-Za-z0-9_-]+/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', (string) $client['name']) ?: 'client');
$pdf->Output('D', $labels['filename'] . '_' . trim((string) $safeClient, '_') . '.pdf');
