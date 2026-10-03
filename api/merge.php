<?php
declare(strict_types=1);
require __DIR__ . '/../lib/helpers.php';
apiBootstrap();

try {
    $files = takeUploads('files', 60);
    $name = cleanOutputName((string) ($_POST['name'] ?? ''), 'pdf');
    $mode = (($_POST['imgmode'] ?? 'a4') === 'real') ? 'real' : 'a4';
    $margin = (float) ($_POST['margin'] ?? 10);
    if ($margin < 0 || $margin > 60) {
        $margin = 10.0;
    }

    $proc = new PdfProcessor();
    foreach ($files as $f) {
        if ($f['ext'] === 'pdf') {
            $n = $proc->open($f['path']);
            $proc->addPdfPages($f['path'], range(1, $n));
        } else {
            $proc->addImage($f['path'], $mode, $margin);
        }
    }

    $out = outputDir() . '/merged-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.pdf';
    $proc->render($out);
    purgeRequestFiles($files);
    serveFile($out, $name, 'application/pdf');
} catch (Throwable $e) {
    failJson('Gagal menggabungkan: ' . mb_substr($e->getMessage(), 0, 300), 422);
}
