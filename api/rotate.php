<?php
declare(strict_types=1);
require __DIR__ . '/../lib/helpers.php';
apiBootstrap();

try {
    $files = takeUploads('file', 1);
    $f = $files[0];
    if ($f['ext'] !== 'pdf') {
        purgeRequestFiles($files);
        failJson('Rotasi hanya mendukung file PDF.');
    }
    $name = cleanOutputName((string) ($_POST['name'] ?? ''), 'pdf');

    $proc = new PdfProcessor();
    $total = $proc->open($f['path']);
    $rotIn = array_map('intval', (array) ($_POST['rot'] ?? []));
    $rotations = [];
    for ($p = 1; $p <= $total; $p++) {
        $r = (int) ($rotIn[$p - 1] ?? 0);
        $rotations[$p] = (($r % 360) + 360) % 360;
        if ($rotations[$p] % 90 !== 0) {
            $rotations[$p] = 0;
        }
    }
    $proc->addPdfPages($f['path'], range(1, $total), $rotations);

    $out = outputDir() . '/rotate-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.pdf';
    $proc->render($out);
    purgeRequestFiles($files);
    serveFile($out, $name, 'application/pdf');
} catch (Throwable $e) {
    failJson('Gagal memproses: ' . mb_substr($e->getMessage(), 0, 300), 422);
}
