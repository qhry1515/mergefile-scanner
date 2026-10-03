<?php
declare(strict_types=1);
require __DIR__ . '/../lib/helpers.php';
apiBootstrap();

try {
    $files = takeUploads('file', 1);
    $f = $files[0];
    if ($f['ext'] !== 'pdf') {
        purgeRequestFiles($files);
        failJson('Ekstraksi teks hanya mendukung file PDF.');
    }

    $parser = new \Smalot\PdfParser\Parser();
    try {
        $pdf = $parser->parseFile($f['path']);
    } catch (\Smalot\PdfParser\Exception\InvalidPdfException $e) {
        purgeRequestFiles($files);
        failJson('File ini bukan PDF yang valid.', 422);
    }

    $pages = $pdf->getPages();
    $parts = [];
    foreach ($pages as $page) {
        $parts[] = trim($page->getText());
    }
    $text = implode("\n\n", $parts);
    $numPages = count($parts);
    purgeRequestFiles($files);

    jsonOut([
        'pages' => $numPages,
        'chars' => strlen($text),
        'text' => $text,
    ]);
} catch (Throwable $e) {
    failJson('Gagal mengekstrak teks: ' . mb_substr($e->getMessage(), 0, 300), 422);
}
