<?php
declare(strict_types=1);
require __DIR__ . '/lib/helpers.php';

$ico = [
    'merge' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5"/><path d="M4 8v11a2 2 0 0 0 2 2h9"/></svg>',
    'split' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6l4 4v14a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M15 3v4h4"/><path d="M12 9v2M12 13v2M12 17v2"/></svg>',
    'rotate' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6l4 4v14a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M15 3v4h4"/><path d="M6.5 14a4.5 4.5 0 0 1 4.4-4.5"/><path d="M11.5 7.5l-.4 2.6-2.6-.4"/></svg>',
    'pagenum' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h6l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M13 3v5h5"/><path d="M9 13h6M9 17h4"/></svg>',
    'compress' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6l4 4v14a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M15 3v4h4"/><path d="M8 12l4 4 4-4M12 16V9"/></svg>',
    'pdf2img' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 16l-5-5-6 6-2-2-5 4"/></svg>',
    'pdf2text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h6l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M13 3v5h5"/><path d="M9 14h1.5a1.5 1.5 0 0 0 0-3H9v6M14 11v6"/></svg>',
    'ocr' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M7 9h4M7 13h6"/><circle cx="16.5" cy="14.5" r="2.5"/><path d="M18.6 16.6L20.5 18.5"/></svg>',
    'receive' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M12 16.5V8.5m0 8l-2.6-2.6M12 16.5l2.6-2.6"/><path d="M10.5 5.5h3"/></svg>',
];

$tools = [
    'merge' => 'Gabung',
    'receive' => 'Transfer',
    'split' => 'Split',
    'rotate' => 'Rotasi',
    'pagenum' => 'Nomor',
    'compress' => 'Kompres',
    'pdf2img' => 'PDF→Gambar',
    'pdf2text' => 'PDF→Teks',
    'ocr' => 'Gambar→Teks',
];

$tool = 'merge';
if (isset($_GET['tool']) && isset($tools[$_GET['tool']])) {
    $tool = (string) $_GET['tool'];
}

cleanupOld(uploadsDir(), 6);
cleanupOld(outputDir(), 6);

if ($tool === 'receive' || isset($_GET['k'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
}

$maxUpload = uploadLimitBytes();
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($tools[$tool], ENT_QUOTES) ?> · MergeFile</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='4' y='3' width='13' height='17' rx='2' fill='%232563eb'/%3E%3Cpath d='M8 7h10a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8' fill='%2360a5fa'/%3E%3C/svg%3E">
<script>
try {
  var t = localStorage.getItem('mf-theme');
  if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  document.documentElement.dataset.theme = t;
} catch (e) {}
</script>
<link rel="stylesheet" href="assets/css/app.css">
<script defer src="assets/vendor/pdfjs/pdf.min.js"></script>
<script defer src="assets/js/app.js"></script>
<?php if ($tool === 'receive'): ?>
<script defer src="assets/vendor/qrcode.min.js"></script>
<?php endif; ?>
<?php if ($tool === 'ocr'): ?>
<script defer src="assets/vendor/tesseract/tesseract.min.js"></script>
<?php endif; ?>
</head>
<body data-tool="<?= htmlspecialchars($tool, ENT_QUOTES) ?>" data-max-upload="<?= (int) $maxUpload ?>">
<header class="nav">
  <div class="nav-inner">
    <a class="brand" href="?tool=merge">
      <span class="brand-mark"><svg viewBox="0 0 24 24" fill="none"><rect x="4" y="3" width="12" height="16" rx="2" fill="currentColor" opacity=".85"/><path d="M8 7h9a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8" fill="currentColor" opacity=".5"/></svg></span>
      <span>MergeFile</span>
    </a>
    <nav class="nav-links">
      <?php foreach ($tools as $key => $label): ?>
      <a class="nav-link<?= $tool === $key ? ' active' : '' ?>" href="?tool=<?= $key ?>"><span class="nav-ico"><?= $ico[$key] ?></span><span><?= htmlspecialchars($label, ENT_QUOTES) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <button id="themeBtn" class="icon-btn" type="button" title="Ganti tema terang/gelap" aria-label="Ganti tema">
      <svg class="ic-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>
      <svg class="ic-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
    </button>
  </div>
</header>
<main class="container">
<?php require appDir('pages/' . $tool . '.php'); ?>
</main>
<footer class="footer">
  <div>Berjalan 100% lokal di perangkat Anda — file tidak pernah dikirim ke internet.</div>
  <div class="muted small">Batas upload PHP: <?= htmlspecialchars(humanSize($maxUpload), ENT_QUOTES) ?></div>
</footer>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script defer src="assets/js/tools/<?= $tool ?>.js"></script>
</body>
</html>
