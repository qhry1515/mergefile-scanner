<?php
declare(strict_types=1);

function ensureDir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function appDir(string $sub = ''): string
{
    return __DIR__ . '/../' . ($sub !== '' ? $sub : '');
}

function uploadsDir(): string
{
    $d = appDir('uploads');
    ensureDir($d);
    return $d;
}

function outputDir(): string
{
    $d = appDir('output');
    ensureDir($d);
    return $d;
}

function inboxDir(): string
{
    $d = appDir('inbox');
    ensureDir($d);
    return $d;
}

function getLanIps(): array
{
    $found = [];
    $out = [];
    if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
        @exec('ipconfig 2>NUL', $out);
        foreach ($out as $line) {
            if (preg_match('/IPv4[^:]*:\s*(\d{1,3}(?:\.\d{1,3}){3})/', $line, $m)) {
                $found[] = $m[1];
            }
        }
    }
    if (!$found) {
        $h = @gethostbyname(gethostname() ?: 'localhost');
        if ($h && preg_match('/^\d{1,3}(?:\.\d{1,3}){3}$/', $h) && strpos($h, '127.') !== 0) {
            $found[] = $h;
        }
    }
    $found = array_values(array_unique(array_filter($found, static function ($ip) {
        if (strpos($ip, '127.') === 0 || $ip === '0.0.0.0') {
            return false;
        }
        $o = explode('.', $ip);
        return count($o) === 4 && intval($o[0]) <= 255 && intval($o[1]) <= 255 && intval($o[2]) <= 255 && intval($o[3]) <= 255;
    })));
    usort($found, static function ($a, $b) {
        $score = static function ($ip) {
            if (strpos($ip, '192.168.') === 0) {
                return 0;
            }
            if (strpos($ip, '10.') === 0) {
                return 1;
            }
            if (preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip)) {
                return 2;
            }
            return 3;
        };
        return $score($a) <=> $score($b);
    });
    return $found;
}

function tunnelStatePath(): string
{
    return appDir('config.tunnel.json');
}

function tunnelLogPath(): string
{
    return appDir('config.tunnel.log');
}

function cloudflaredPath(): string
{
    return appDir('bin/cloudflared.exe');
}

function readTunnelState(): ?array
{
    $p = tunnelStatePath();
    if (!is_file($p)) {
        return null;
    }
    $j = json_decode((string) @file_get_contents($p), true);
    if (!is_array($j) || empty($j['url']) || empty($j['pin'])) {
        return null;
    }
    return $j;
}

function writeTunnelState(array $state): void
{
    @file_put_contents(tunnelStatePath(), json_encode($state, JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function clearTunnelState(): void
{
    @unlink(tunnelStatePath());
    @unlink(tunnelLogPath());
    @unlink(appDir('config.tunnel.out.log'));
}

function cloudflaredRunning(): bool
{
    if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
        return false;
    }
    $out = [];
    @exec('tasklist /FI "IMAGENAME eq cloudflared.exe" /NH 2>NUL', $out);
    foreach ($out as $line) {
        if (stripos($line, 'cloudflared.exe') !== false) {
            return true;
        }
    }
    return false;
}

function tunnelActive(): bool
{
    $state = readTunnelState();
    if (!$state) {
        return false;
    }
    if (!cloudflaredRunning()) {
        clearTunnelState();
        return false;
    }
    return true;
}

function findTailscale(): ?string
{
    static $checked = false;
    static $bin = null;
    if ($checked) {
        return $bin;
    }
    $checked = true;
    $candidates = [];
    $out = [];
    @exec('where tailscale 2>NUL', $out);
    foreach ($out as $line) {
        $line = trim($line);
        if ($line !== '') {
            $candidates[] = $line;
        }
    }
    $candidates[] = 'C:\\Program Files\\Tailscale\\tailscale.exe';
    $candidates[] = 'C:\\Program Files (x86)\\Tailscale\\tailscale.exe';
    foreach ($candidates as $c) {
        if (is_file($c)) {
            $bin = $c;
            break;
        }
    }
    return $bin;
}

function getTailscaleIp(): ?string
{
    $bin = findTailscale();
    if ($bin) {
        $out = [];
        @exec('"' . $bin . '" ip -4 2>NUL', $out);
        foreach ($out as $line) {
            $line = trim($line);
            if (filter_var($line, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $line;
            }
        }
    }
    $out = [];
    @exec('ipconfig 2>NUL', $out);
    foreach ($out as $line) {
        if (preg_match('/IPv4[^:]*:\s*(100\.(?:6[4-9]|1[01]\d|12[0-7])\.\d{1,3}\.\d{1,3})/', $line, $m)) {
            return $m[1];
        }
    }
    return null;
}

function requestHost(): string
{
    $h = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (preg_match('/^(\[[^\]]+\]|[^:]+)(?::\d+)?$/', $h, $m)) {
        $h = $m[1];
    }
    return trim($h, '[]');
}

function isTrustedLocalRequest(): bool
{
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($remote !== '127.0.0.1' && $remote !== '::1' && strpos($remote, '127.') !== 0) {
        return false;
    }
    $host = requestHost();
    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
        return true;
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (in_array($host, getLanIps(), true)) {
            return true;
        }
        $ts = getTailscaleIp();
        if ($ts !== null && $host === $ts) {
            return true;
        }
    }
    return false;
}

function tunnelSessionStart(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
}

function tunnelAuthSessionSet(?array $state): void
{
    if (!$state) {
        return;
    }
    tunnelSessionStart();
    $_SESSION['rf_pin'] = (string) $state['pin'];
}

function tunnelAuthed(?array $state): bool
{
    if (!$state) {
        return true;
    }
    if (isTrustedLocalRequest()) {
        return true;
    }
    if (!empty($state['locked'])) {
        return false;
    }
    tunnelSessionStart();
    $key = (string) ($_GET['k'] ?? '');
    $stateKey = (string) ($state['key'] ?? '');
    if ($key !== '' && $stateKey !== '' && hash_equals($stateKey, $key)) {
        return true;
    }
    if (isset($_SESSION['rf_pin']) && hash_equals((string) $state['pin'], (string) $_SESSION['rf_pin'])) {
        return true;
    }
    return false;
}

function tunnelNotePinFailure(?array $state): ?array
{
    if (!$state) {
        return null;
    }
    $state['fails'] = (int) ($state['fails'] ?? 0) + 1;
    if ((int) $state['fails'] >= 25) {
        $state['locked'] = true;
    }
    writeTunnelState($state);
    return $state;
}

function cleanupOld(string $dir, int $hours = 6): void
{
    if (!is_dir($dir)) {
        return;
    }
    $limit = time() - $hours * 3600;
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        $p = $dir . '/' . $f;
        if (is_file($p)) {
            $m = @filemtime($p);
            if ($m !== false && $m < $limit) {
                @unlink($p);
            }
        }
    }
}

function jsonOut(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function failJson(string $message, int $code = 400): never
{
    jsonOut(['error' => $message], $code);
}

function appConfigPath(): string
{
    return appDir('config.local.php');
}

function appConfig(): array
{
    $p = appConfigPath();
    if (is_file($p)) {
        $c = require $p;
        if (is_array($c)) {
            return $c;
        }
    }
    return [];
}

function appConfigSave(array $values): void
{
    $current = appConfig();
    foreach ($values as $k => $v) {
        if (is_string($k) && preg_match('/^[a-z0-9_]+$/', $k) && (is_string($v) || is_int($v))) {
            $current[$k] = $v;
        }
    }
    file_put_contents(appConfigPath(), "<?php\nreturn " . var_export($current, true) . ";\n", LOCK_EX);
}

function parseSize(string $size): int
{
    $size = trim($size);
    if ($size === '') {
        return 0;
    }
    $unit = strtolower(substr($size, -1));
    $val = (float) $size;
    if ($unit === 'g') {
        $val *= 1073741824;
    } elseif ($unit === 'm') {
        $val *= 1048576;
    } elseif ($unit === 'k') {
        $val *= 1024;
    }
    return (int) $val;
}

function uploadLimitBytes(): int
{
    return min(parseSize((string) ini_get('upload_max_filesize')), parseSize((string) ini_get('post_max_size')));
}

function humanSize(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 1) . ' GB';
    }
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

function uploadErrorText(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ukuran file melebihi batas PHP (' . ini_get('upload_max_filesize') . ').';
        case UPLOAD_ERR_PARTIAL:
            return 'File terkirim tidak lengkap. Coba lagi.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Folder sementara PHP tidak ditemukan.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Gagal menulis file ke disk. Periksa izin folder uploads.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload dihentikan oleh ekstensi PHP.';
        default:
            return 'Terjadi kesalahan saat upload (kode ' . $code . ').';
    }
}

function allowedImageExts(): array
{
    return ['jpg', 'jpeg', 'png', 'gif'];
}

function takeUploads(string $field, int $maxCount = 60): array
{
    if (!isset($_FILES[$field])) {
        failJson('Tidak ada file yang dikirim.');
    }
    $f = $_FILES[$field];
    if (!is_array($f['name'])) {
        $f = [
            'name' => [(string) $f['name']],
            'tmp_name' => [(string) $f['tmp_name']],
            'error' => [(int) $f['error']],
            'size' => [(int) $f['size']],
        ];
    }
    $n = count($f['name']);
    if ($n === 0) {
        failJson('Tidak ada file yang dikirim.');
    }
    if ($n > $maxCount) {
        failJson('Maksimal ' . $maxCount . ' file per proses.');
    }
    $limit = uploadLimitBytes();
    $dir = uploadsDir();
    $out = [];
    for ($i = 0; $i < $n; $i++) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($f['error'][$i] !== UPLOAD_ERR_OK) {
            failJson(uploadErrorText((int) $f['error'][$i]));
        }
        $size = (int) $f['size'][$i];
        if ($limit > 0 && $size > $limit) {
            failJson('File "' . basename((string) $f['name'][$i]) . '" terlalu besar. Batas ' . humanSize($limit) . '.');
        }
        $orig = basename((string) $f['name'][$i]);
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if ($ext !== 'pdf' && !in_array($ext, allowedImageExts(), true)) {
            failJson('Format .' . $ext . ' tidak didukung. Gunakan PDF, JPG, PNG, atau GIF.');
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (finfo_file($finfo, (string) $f['tmp_name'][$i]) ?: '') : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        if ($ext === 'pdf') {
            if (!preg_match('#^application/(pdf|x-pdf|octet-stream)$#', $mime)) {
                failJson('File "' . $orig . '" bukan PDF yang valid.');
            }
        } else {
            if (!preg_match('#^image/(jpeg|png|gif)$#', $mime)) {
                failJson('File "' . $orig . '" bukan gambar JPG/PNG/GIF yang valid.');
            }
        }
        $target = $dir . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file((string) $f['tmp_name'][$i], $target)) {
            failJson('Gagal menyimpan file ke server.', 500);
        }
        $out[] = ['path' => $target, 'name' => $orig, 'size' => $size, 'ext' => $ext];
    }
    if (!$out) {
        failJson('Tidak ada file valid yang diterima.');
    }
    return $out;
}

function cleanOutputName(string $name, string $ext): string
{
    $name = trim($name);
    $name = preg_replace('/[\x00-\x1F<>:"\/\\\\|?*]/u', '', $name) ?? '';
    $name = trim($name, " .\t\n\r");
    if (mb_strlen($name) > 100) {
        $name = mb_substr($name, 0, 100);
    }
    if ($name === '') {
        $name = 'dokumen';
    }
    if (strtolower((string) pathinfo($name, PATHINFO_EXTENSION)) !== strtolower($ext)) {
        $name .= '.' . $ext;
    }
    return $name;
}

function serveFile(string $path, string $downloadName, string $mime): never
{
    if (!is_file($path)) {
        failJson('Hasil tidak ditemukan.', 500);
    }
    $size = filesize($path);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
    header('Content-Length: ' . (string) $size);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    readfile($path);
    exit;
}

function purgeRequestFiles(array $files): void
{
    foreach ($files as $f) {
        if (is_string($f['path'] ?? null) && is_file($f['path'])) {
            @unlink($f['path']);
        }
    }
}

function apiBootstrap(): void
{
    set_time_limit(180);
    ini_set('memory_limit', '512M');
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/PdfProcessor.php';
    cleanupOld(uploadsDir(), 6);
    cleanupOld(outputDir(), 6);
}

function findGhostscript(): ?string
{
    $cfg = appConfig();
    $configured = trim((string) ($cfg['gs_path'] ?? ''));
    if ($configured !== '' && is_file($configured)) {
        return $configured;
    }
    $names = ['gswin64c', 'gswin32c', 'gs'];
    foreach ($names as $n) {
        $out = [];
        $rc = 0;
        @exec('where ' . $n . ' 2>NUL', $out, $rc);
        if ($rc === 0 && !empty($out[0]) && is_file($out[0])) {
            return $out[0];
        }
    }
    $globs = [
        'C:/Program Files/gs/gs*/bin/gswin64c.exe',
        'C:/Program Files/gs/gs*/bin/gswin32c.exe',
        'C:/Program Files (x86)/gs/gs*/bin/gswin32c.exe',
        'C:/gs/bin/gswin64c.exe',
    ];
    foreach ($globs as $g) {
        $hits = glob($g) ?: [];
        if ($hits) {
            usort($hits, function ($a, $b) {
                return strcmp(basename(dirname(dirname($b))), basename(dirname(dirname($a))));
            });
            return $hits[0];
        }
    }
    return null;
}
