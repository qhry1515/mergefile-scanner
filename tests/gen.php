<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$outDir = __DIR__ . '/../output/tests';
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(false, 0);
foreach ([1, 2, 3] as $n) {
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 32);
    $pdf->SetXY(20, 100);
    $pdf->Cell(170, 20, 'HALAMAN ' . $n, 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 14);
    $pdf->SetXY(20, 130);
    $pdf->Cell(170, 10, 'Dokumen uji MergeFile', 0, 1, 'C');
    $pdf->SetFillColor(30, 64, 175);
    $pdf->Rect(20, 20, 40, 30, 'F');
}
$pdf->Output('F', $outDir . '/sample-3hal.pdf');

function makePng(int $w, int $h): string
{
    $raw = '';
    for ($y = 0; $y < $h; $y++) {
        $raw .= "\x00";
        for ($x = 0; $x < $w; $x++) {
            $raw .= chr(($x * 5) % 256) . chr(($y * 3) % 256) . chr(140);
        }
    }
    $idat = gzcompress($raw, 9);
    $chunk = static function (string $type, string $data): string {
        return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    };
    $ihdr = pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0);
    return "\x89PNG\r\n\x1a\n" . $chunk('IHDR', $ihdr) . $chunk('IDAT', $idat) . $chunk('IEND', '');
}

file_put_contents($outDir . '/sample-gambar.png', makePng(120, 180));

$jpgSrc = 'C:/xampp/htdocs/belajar-crud/uploads/2a113a42539cfb455b5085ce531cebd6.jpg';
if (is_file($jpgSrc)) {
    copy($jpgSrc, $outDir . '/sample-gambar.jpg');
}

$rot = new FPDF('P', 'mm', 'A4');
$rot->SetAutoPageBreak(false, 0);
$rot->AddPage('P', [210, 297]);
$rot->SetFont('Helvetica', 'B', 40);
$rot->SetXY(20, 60);
$rot->Cell(170, 20, 'NORMAL 0', 0, 1, 'C');
$rot->SetFillColor(220, 38, 38);
$rot->Rect(20, 20, 30, 30, 'F');
$rot->AddPage('P', [210, 297], 90);
$rot->SetFont('Helvetica', 'B', 40);
$rot->SetXY(20, 60);
$rot->Cell(170, 20, 'ROTATED 90', 0, 1, 'C');
$rot->SetFillColor(22, 163, 74);
$rot->Rect(20, 20, 30, 30, 'F');
$rot->Output('F', $outDir . '/sample-rotated.pdf');

foreach (glob($outDir . '/*') as $f) {
    if (pathinfo($f, PATHINFO_EXTENSION) === 'php') {
        continue;
    }
    $info = @getimagesize($f);
    echo basename($f) . ' — ' . filesize($f) . ' B' . ($info ? ' [' . $info[0] . 'x' . $info[1] . ' ' . $info['mime'] . ']' : ' (pdf)') . "\n";
}
