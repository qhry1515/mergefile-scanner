<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$path = $argv[1] ?? '';
if (!is_file($path)) {
    fwrite(STDERR, "File tidak ada: {$path}\n");
    exit(1);
}

$parser = new \Smalot\PdfParser\Parser();
try {
    $pdf = $parser->parseFile($path);
    $pages = $pdf->getPages();
    echo basename($path) . ' — ' . count($pages) . ' halaman\n';
    foreach ($pages as $i => $page) {
        $text = trim(preg_replace('/\s+/', ' ', $page->getText()));
        echo '  [' . ($i + 1) . '] ' . mb_substr($text, 0, 120) . "\n";
    }
} catch (Throwable $e) {
    echo 'GAGAL: ' . $e->getMessage() . "\n";
    exit(2);
}
