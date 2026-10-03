<?php
declare(strict_types=1);
require __DIR__ . '/../lib/helpers.php';
apiBootstrap();

if (($_GET['detect'] ?? '') === '1' || ($_GET['config'] ?? '') === '1') {
    if (($_GET['config'] ?? '') === '1') {
        appConfigSave(['gs_path' => trim((string) ($_POST['gs_path'] ?? ''))]);
    }
    $cfg = appConfig();
    jsonOut([
        'gs' => findGhostscript(),
        'configured' => (string) ($cfg['gs_path'] ?? ''),
    ]);
}

try {
    $files = takeUploads('file', 1);
    $f = $files[0];
    if ($f['ext'] !== 'pdf') {
        purgeRequestFiles($files);
        failJson('Kompres hanya mendukung file PDF.');
    }
    if (array_key_exists('gs_path', $_POST)) {
        appConfigSave(['gs_path' => trim((string) $_POST['gs_path'])]);
    }
    $name = cleanOutputName((string) ($_POST['name'] ?? ''), 'pdf');
    $light = isset($_POST['light']);
    $presets = ['screen', 'ebook', 'printer'];
    $preset = (string) ($_POST['preset'] ?? 'ebook');
    if (!in_array($preset, $presets, true)) {
        $preset = 'ebook';
    }

    $out = outputDir() . '/compress-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.pdf';

    if (!$light) {
        $gs = findGhostscript();
        if ($gs) {
            $cmd = sprintf(
                '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/%s -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                escapeshellarg($gs),
                $preset,
                escapeshellarg($out),
                escapeshellarg($f['path'])
            );
            $output = [];
            $rc = 0;
            exec($cmd, $output, $rc);
            if ($rc === 0 && is_file($out) && filesize($out) > 0) {
                purgeRequestFiles($files);
                serveFile($out, $name, 'application/pdf');
            }
            @unlink($out);
            failJson('Ghostscript gagal memproses file ini. Coba mode Optimasi Ringan.', 422);
        }
        failJson('Ghostscript belum terdeteksi. Isi lokasi file gswin64c.exe atau gunakan Optimasi Ringan.', 400);
    }

    $proc = new PdfProcessor();
    $total = $proc->open($f['path']);
    $proc->addPdfPages($f['path'], range(1, $total));
    $proc->render($out);
    purgeRequestFiles($files);
    serveFile($out, $name, 'application/pdf');
} catch (Throwable $e) {
    failJson('Gagal mengompres: ' . mb_substr($e->getMessage(), 0, 300), 422);
}
