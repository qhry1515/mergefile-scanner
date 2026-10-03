<?php
declare(strict_types=1);

use setasign\Fpdi\Fpdi;

class PdfOut extends Fpdi
{
    public function outRaw(string $s): void
    {
        $this->_out($s);
    }
}

class PdfProcessor
{
    public PdfOut $pdf;
    private ?string $currentPath = null;
    private int $pageCount = 0;

    public function __construct()
    {
        $this->pdf = new PdfOut();
        $this->pdf->SetAutoPageBreak(false, 0);
    }

    public function open(string $path): int
    {
        if ($this->currentPath !== $path) {
            $this->pageCount = $this->pdf->setSourceFile($path);
            $this->currentPath = $path;
        }
        return $this->pageCount;
    }

    public function addPdfPages(string $path, array $pages, array $rotations = [], ?array $stamp = null): void
    {
        $total = $this->open($path);
        $i = 0;
        foreach ($pages as $p) {
            $p = (int) $p;
            if ($p < 1 || $p > $total) {
                throw new InvalidArgumentException('Halaman ' . $p . ' tidak ada dalam dokumen (total ' . $total . ' halaman).');
            }
            $rot = (int) ($rotations[$p] ?? 0);
            $this->placeImportedPage($p, $rot);
            if ($stamp !== null) {
                $this->stampNumber($i, $total, $stamp);
            }
            $i++;
        }
    }

    public function addImage(string $path, string $mode, float $margin): void
    {
        $dims = @getimagesize($path);
        if (!$dims || $dims[0] < 1 || $dims[1] < 1) {
            throw new InvalidArgumentException('Gambar tidak valid atau format tidak didukung.');
        }
        $iw = (int) $dims[0];
        $ih = (int) $dims[1];
        if ($mode === 'real') {
            $w = $iw * 25.4 / 96;
            $h = $ih * 25.4 / 96;
            $this->pdf->AddPage($w > $h ? 'L' : 'P', [$w, $h]);
            $this->pdf->Image($path, 0, 0, $w, $h);
        } else {
            $landscape = $iw > $ih;
            $pw = $landscape ? 297.0 : 210.0;
            $ph = $landscape ? 210.0 : 297.0;
            $availW = max(1.0, $pw - 2 * $margin);
            $availH = max(1.0, $ph - 2 * $margin);
            $ratio = $iw / $ih;
            $w = min($availW, $availH * $ratio);
            $h = $w / $ratio;
            $x = ($pw - $w) / 2;
            $y = ($ph - $h) / 2;
            $this->pdf->AddPage($landscape ? 'L' : 'P', [$pw, $ph]);
            $this->pdf->Image($path, $x, $y, $w, $h);
        }
    }

    private function placeImportedPage(int $pageNum, int $rot): void
    {
        $rot = (($rot % 360) + 360) % 360;
        if ($rot % 90 !== 0) {
            $rot = 0;
        }
        $tpl = $this->pdf->importPage($pageNum);
        $size = $this->pdf->getTemplateSize($tpl);
        $w = (float) $size['width'];
        $h = (float) $size['height'];
        if ($rot === 90 || $rot === 270) {
            $pw = $h;
            $ph = $w;
        } else {
            $pw = $w;
            $ph = $h;
        }
        $this->pdf->AddPage($pw > $ph ? 'L' : 'P', [$pw, $ph]);
        if ($rot === 0) {
            $this->pdf->useTemplate($tpl, 0, 0);
            return;
        }
        $wPt = $w * (72 / 25.4);
        $hPt = $h * (72 / 25.4);
        switch ($rot) {
            case 90:
                $m = [0, -1, 1, 0, 0, $wPt];
                break;
            case 180:
                $m = [-1, 0, 0, -1, $wPt, $hPt];
                break;
            default:
                $m = [0, 1, -1, 0, $hPt, 0];
        }
        $this->pdf->outRaw(sprintf('q %.5F %.5F %.5F %.5F %.5F %.5F cm', $m[0], $m[1], $m[2], $m[3], $m[4], $m[5]));
        $this->pdf->useTemplate($tpl, 0, $ph - $h);
        $this->pdf->outRaw('Q');
    }

    private function stampNumber(int $index, int $total, array $opts): void
    {
        $pos = (string) ($opts['pos'] ?? 'bc');
        $fmt = (string) ($opts['format'] ?? '{n}/{t}');
        $font = (string) ($opts['font'] ?? 'Helvetica');
        $style = (string) ($opts['style'] ?? '');
        $size = max(6.0, min(40.0, (float) ($opts['size'] ?? 10)));
        $margin = max(2.0, min(50.0, (float) ($opts['margin'] ?? 12)));
        $start = (int) ($opts['start'] ?? 1);
        $color = (string) ($opts['color'] ?? '#333333');
        if (!in_array($font, ['Helvetica', 'Times', 'Courier'], true)) {
            $font = 'Helvetica';
        }
        if (!in_array($style, ['', 'B', 'I', 'BI'], true)) {
            $style = '';
        }
        $num = $start + $index;
        $text = strtr($fmt, ['{n}' => (string) $num, '{t}' => (string) $total]);
        [$r, $g, $b] = self::hexToRgb($color);
        $this->pdf->SetFont($font, $style, $size);
        $this->pdf->SetTextColor($r, $g, $b);
        $this->pdf->SetDrawColor($r, $g, $b);
        $pw = $this->pdf->GetPageWidth();
        $ph = $this->pdf->GetPageHeight();
        $cellH = $size / 72 * 25.4 * 1.5;
        $y = ($pos[0] === 't') ? $margin : $ph - $margin - $cellH;
        $y = max(0.5, min($y, max(0.5, $ph - 1)));
        $align = $pos[1] === 'l' ? 'L' : ($pos[1] === 'r' ? 'R' : 'C');
        $this->pdf->SetXY(min($margin, max(0.0, $pw / 2)), $y);
        $this->pdf->Cell(max(1.0, $pw - 2 * $margin), $cellH, $text, 0, 0, $align);
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '333333';
        }
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public function render(string $outPath): void
    {
        if ($this->pdf->PageNo() < 1) {
            throw new RuntimeException('Tidak ada halaman yang diproses.');
        }
        $this->pdf->Output('F', $outPath);
    }
}
