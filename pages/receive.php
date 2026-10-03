<?php
$port = (int) ($_SERVER['SERVER_PORT'] ?? 80);
$suffix = $port !== 80 ? ':' . $port : '';
$ips = getLanIps();
$urls = [];
foreach ($ips as $ip) {
    $urls[] = 'http://' . $ip . $suffix . '/?tool=receive';
}
$primary = $urls[0] ?? '';
$hasLan = (bool) $urls;
$tsIp = getTailscaleIp();
$tsUrl = $tsIp !== null ? 'http://' . $tsIp . $suffix . '/?tool=receive' : '';
$tunnel = tunnelActive() ? readTunnelState() : null;
$trusted = isTrustedLocalRequest();
$keyQuery = (string) ($_GET['k'] ?? '');
if ($tunnel && !$trusted && $keyQuery !== '' && (string) ($tunnel['key'] ?? '') !== ''
    && hash_equals((string) $tunnel['key'], $keyQuery)) {
    tunnelAuthSessionSet($tunnel);
    $trusted = true;
}
$locked = $tunnel !== null && !empty($tunnel['locked']);
$tunnelBase = $tunnel ? (string) $tunnel['url'] : '';
$tunnelKey = ($tunnel && $trusted) ? (string) ($tunnel['key'] ?? '') : '';
$needPin = $tunnel !== null && !$trusted;
?>
<div class="page-head">
  <h2>Transfer HP → Laptop</h2>
  <p>Kirim foto/file dari iPhone (atau HP apa pun) ke laptop — lewat WiFi yang sama, Tailscale, atau link internet. Tanpa WhatsApp, tanpa email, tanpa kabel.</p>
</div>

<div class="panel" id="pinGate" hidden>
  <b>Masukkan PIN</b>
  <div class="muted small" style="margin-top:4px">Transfer terkunci karena link internet aktif. PIN tampil di layar laptop (buka tool ini di laptop).</div>
  <div class="row" style="gap:8px;margin-top:10px;max-width:340px">
    <input type="text" id="pinInput" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6 digit" style="text-align:center;letter-spacing:6px;font-weight:700">
    <button class="btn btn-primary" id="pinBtn" type="button">Buka</button>
  </div>
  <div class="muted small" id="pinErr" style="color:var(--danger);margin-top:8px" hidden>PIN salah. Coba lagi.</div>
</div>

<div class="panel" id="connectPanel"
     data-urls="<?= htmlspecialchars((string) json_encode($urls), ENT_QUOTES) ?>"
     data-tunnel-base="<?= htmlspecialchars($tunnelBase, ENT_QUOTES) ?>"
     data-tunnel-key="<?= htmlspecialchars($tunnelKey, ENT_QUOTES) ?>"
     data-need-pin="<?= $needPin ? '1' : '0' ?>">
  <?php if ($locked): ?>
  <div class="notice warn" style="margin-bottom:14px">
    <div><b>Link terkunci.</b> Terlalu banyak percobaan PIN salah. Matikan lalu buat link baru untuk mendapat PIN & kunci baru.</div>
  </div>
  <?php endif; ?>
  <div class="row wrap" style="gap:20px;align-items:flex-start">
    <div style="flex-shrink:0;text-align:center">
      <div id="qr" style="background:#fff;padding:10px;border-radius:12px;display:inline-block"<?= $hasLan || $tunnelBase ? '' : ' hidden' ?>></div>
      <div class="muted small" id="qrLabel" style="margin-top:6px"></div>
    </div>
    <div class="grow" style="min-width:240px">
      <div class="muted small" style="text-transform:uppercase;letter-spacing:.5px;font-weight:700;margin-bottom:6px">Buka di HP Anda</div>
      <div class="row wrap" style="gap:8px">
        <?php if ($hasLan): ?>
        <a id="lanUrl" class="btn btn-primary" href="<?= htmlspecialchars($primary, ENT_QUOTES) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($primary, ENT_QUOTES) ?></a>
        <button class="btn btn-outline btn-sm" id="copyUrl" type="button">Salin URL</button>
        <?php else: ?>
        <a id="lanUrl" class="btn btn-primary" href="#" hidden></a>
        <?php endif; ?>
        <?php if ($tsUrl !== ''): ?>
        <a class="btn btn-outline btn-sm" href="<?= htmlspecialchars($tsUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener" title="Tailscale — akses dari jaringan apa pun">Tailscale: <?= htmlspecialchars($tsIp, ENT_QUOTES) ?></a>
        <?php endif; ?>
      </div>
      <p class="muted small" style="margin:10px 0 0">
        Pastikan laptop dan HP terhubung <b>WiFi yang sama</b>. Pindai QR dengan kamera iPhone, atau ketik URL di atas di Safari.
        Saat Windows menampilkan pertanyaan firewall untuk PHP, pilih <b>Izinkan akses</b> (jaringan pribadi).
      </p>
      <p class="muted small" id="noLan" <?= $hasLan ? 'hidden' : '' ?> style="color:var(--warning);margin-top:8px">
        IP lokal tidak terdeteksi — buka CMD, ketik <code>ipconfig</code>, pakai IPv4 Anda, lalu akses <code>http://IP-ANDA<?= $suffix ?>/?tool=receive</code> dari HP.
      </p>
    </div>
  </div>

  <div class="divider" style="margin-top:16px"></div>

  <div class="row wrap" style="justify-content:space-between;align-items:center;gap:10px;margin-top:14px">
    <div class="grow" style="min-width:220px">
      <b>Akses dari luar (internet)</b>
      <div class="muted small">HP cukup <b>pindai QR</b> — kunci &amp; PIN ikut tersimpan di dalam QR, jadi tidak perlu ketik atau salin-tempel. HP bisa dari data seluler. Link hidup selama tombol aktif; laptop harus menyala + online.</div>
    </div>
    <div class="row" style="gap:8px">
      <button class="btn btn-primary btn-sm" id="btnTunnel" type="button"><span class="spinner"></span>Buat link internet</button>
      <button class="btn btn-danger-soft btn-sm" id="btnTunnelStop" type="button" hidden>Matikan link</button>
    </div>
  </div>
  <div id="netInfo" hidden style="margin-top:10px">
    <div class="row wrap" style="gap:8px">
      <a id="netUrl" class="btn btn-outline btn-sm grow" href="#" target="_blank" rel="noopener" style="overflow:hidden;text-overflow:ellipsis"></a>
      <button class="btn btn-outline btn-sm" id="copyNet" type="button">Salin</button>
    </div>
    <div id="netPin" class="muted small" style="margin-top:8px" hidden></div>
  </div>

  <div class="divider" style="margin-top:16px"></div>

  <div style="margin-top:14px">
    <b>Jaringan pribadi Tailscale</b>
    <?php if ($tsUrl !== ''): ?>
    <div class="muted small">Terhubung (IP <?= htmlspecialchars($tsIp, ENT_QUOTES) ?>) — HP yang login Tailscale sama bisa akses dari jaringan apa pun, kapan pun.</div>
    <?php else: ?>
    <div class="muted small">Belum terpasang. Untuk akses privat permanen dari mana saja: unduh dari
      <a href="https://tailscale.com/download" target="_blank" rel="noopener">tailscale.com/download</a>,
      instal di laptop + HP (App Store/Play Store), login akun yang sama, lalu buka tool ini lewat IP Tailscale di HP.
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="dropzone" id="drop" style="margin-top:16px">
  <div class="dz-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M4 16v2.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V16"/></svg></div>
  <div class="dz-main">Tarik file ke sini, atau klik untuk pilih</div>
  <div class="dz-sub">Foto, video, PDF, apa saja · di iPhone: pilih "Foto" atau "File" · batas ukuran <span id="dzLimit">40 MB</span></div>
</div>
<input type="file" id="fileInput" multiple hidden>

<div class="hint-bar" style="margin-top:12px">
  <label class="row" style="cursor:pointer;gap:8px">
    <input type="checkbox" id="autoImg" checked style="width:16px;height:16px;accent-color:var(--accent)">
    <span>Kompres gambar otomatis (foto besar → JPG maks. 1920px, jauh lebih cepat)</span>
  </label>
</div>

<div class="progress" id="progress" hidden><i id="progressBar"></i></div>
<div class="hint-bar" id="uploadStatus" hidden></div>

<div class="panel" id="listPanel" style="margin-top:16px">
  <div class="row wrap" style="justify-content:space-between">
    <div class="row" style="gap:8px">
      <b>File masuk</b>
      <span class="muted small" id="listCount">0 file</span>
    </div>
    <div class="row" style="gap:8px">
      <button class="btn btn-outline btn-sm" id="refreshBtn" type="button">Muat ulang</button>
      <button class="btn btn-danger-soft btn-sm" id="clearBtn" type="button">Hapus semua</button>
    </div>
  </div>
  <div id="fileList" style="margin-top:12px">
    <div class="empty" style="margin-top:0" id="listEmpty">Belum ada file masuk.</div>
  </div>
</div>
