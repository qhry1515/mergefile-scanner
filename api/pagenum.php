<?php
declare(strict_types=1);
require __DIR__ . '/../lib/helpers.php';
apiBootstrap();

try {
    $files = takeUploads('file', 1);
    $f = $files[0];
    if ($f['ext'] !== 'pdf') {
        purgeRequestFiles($files);
        failJson('Penomoran hanya mendukung file PDF.');
    }
    $name = cleanOutputName((string) ($_POST['name'] ?? ''), 'pdf');

    $positions = ['bc', 'bl', 'br', 'tc', 'tl', 'tr'];
    $formats = ['{n}/{t}', '{n}', 'Halaman {n} dari {t}', 'Page {n} of {t}', '{n} — {t}'];
    $fonts = ['Helvetica', 'Times', 'Courier'];
    $styles = ['', 'B', 'I'];

    $pos = (string) ($_POST['pos'] ?? 'bc');
    $format = (string) ($_POST['format'] ?? '{n}/{t}');
    $font = (string) ($_POST['font'] ?? 'Helvetica');
    $style = (string) ($_POST['style'] ?? '');

    $stamp = [
        'pos' => in_array($pos, $positions, true) ? $pos : 'bc',
        'format' => in_array($format, $formats, true) ? $format : '{n}/{t}',
        'font' => in_array($font, $fonts, true) ? $font : 'Helvetica',
        'style' => in_array($style, $styles, true) ? $style : '',
        'size' => max(6, min(40, (int) ($_POST['size'] ?? 10))),
        'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST['color'] ?? '')) ? (string) $_POST['color'] : '#333333',
        'margin' => max(2, min(50, (int) ($_POST['margin'] ?? 12))),
        'start' => max(0, min(9999, (int) ($_POST['start'] ?? 1))),
    ];

    $proc = new PdfProcessor();
    $total = $proc->open($f['path']);
    $proc->addPdfPages($f['path'], range(1, $total), [], $stamp);

    $out = outputDir() . '/pagenum-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.pdf';
    $proc->render($out);
    purgeRequestFiles($files);
    serveFile($out, $name, 'application/pdf');
} catch (Throwable $e) {
    failJson('Gagal memproses: ' . mb_substr($e->getMessage(), 0, 300), 422);
}
