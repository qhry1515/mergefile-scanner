<?php
declare(strict_types=1);
require __DIR__ . '/../lib/helpers.php';
apiBootstrap();

try {
    $files = takeUploads('file', 1);
    $f = $files[0];
    if ($f['ext'] !== 'pdf') {
        purgeRequestFiles($files);
        failJson('Split hanya mendukung file PDF.');
    }
    $pages = array_values(array_map('intval', (array) ($_POST['pages'] ?? [])));
    $pages = array_filter($pages, static function ($p) {
        return $p >= 1 && $p <= 9999;
    });
    $pages = array_values($pages);
    if (!$pages) {
        purgeRequestFiles($files);
        failJson('Tidak ada halaman yang dipilih.');
    }
    $name = cleanOutputName((string) ($_POST['name'] ?? ''), 'pdf');

    $proc = new PdfProcessor();
    $proc->addPdfPages($f['path'], $pages);

    $out = outputDir() . '/split-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.pdf';
    $proc->render($out);
    purgeRequestFiles($files);
    serveFile($out, $name, 'application/pdf');
} catch (Throwable $e) {
    failJson('Gagal memproses: ' . mb_substr($e->getMessage(), 0, 300), 422);
}
