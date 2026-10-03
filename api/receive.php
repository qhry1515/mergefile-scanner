<?php
declare(strict_types=1);
require __DIR__ . '/../lib/helpers.php';
apiBootstrap();

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'upload');
$dir = inboxDir();
session_start();

if ($action === 'verify_pin') {
    $pin = (string) ($_POST['pin'] ?? '');
    $st = readTunnelState();
    if (!$st || !cloudflaredRunning()) {
        jsonOut(['ok' => true]);
    }
    if (!empty($st['locked'])) {
        failJson('Link terkunci (terlalu banyak percobaan PIN salah). Buat link baru di laptop.', 429);
    }
    if ($pin !== '' && hash_equals((string) $st['pin'], $pin)) {
        tunnelAuthSessionSet($st);
        jsonOut(['ok' => true]);
    }
    tunnelNotePinFailure($st);
    failJson('PIN salah.', 401);
}

$pinState = readTunnelState();
if ($pinState && cloudflaredRunning() && !tunnelAuthed($pinState)) {
    failJson(!empty($pinState['locked']) ? 'Link terkunci (terlalu banyak percobaan PIN salah). Buat link baru di laptop.' : 'PIN diperlukan.', 401);
}

function receiveMetaPath(string $id): string
{
    return inboxDir() . '/' . $id . '.meta';
}

function receiveSafeName(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
    $name = trim($name);
    if (mb_strlen($name) > 150) {
        $name = mb_substr($name, 0, 150);
    }
    if ($name === '' || $name === '.' || $name === '..') {
        $name = 'file-' . date('Ymd-His') . '.bin';
    }
    return $name;
}

function receiveValidId(string $id): bool
{
    return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
}

if ($action === 'list') {
    $files = [];
    foreach (glob($dir . '/*.meta') ?: [] as $mp) {
        $meta = json_decode((string) @file_get_contents($mp), true);
        if (!is_array($meta) || !isset($meta['id']) || !receiveValidId((string) $meta['id'])) {
            @unlink($mp);
            continue;
        }
        $id = (string) $meta['id'];
        $data = $dir . '/' . $id;
        if (!is_file($data)) {
            @unlink($mp);
            continue;
        }
        $files[] = [
            'id' => $id,
            'name' => (string) ($meta['name'] ?? $id),
            'size' => (int) filesize($data),
            'time' => (int) ($meta['time'] ?? filemtime($data)),
            'mime' => (string) ($meta['mime'] ?? 'application/octet-stream'),
        ];
    }
    usort($files, static function ($a, $b) {
        return $b['time'] <=> $a['time'];
    });
    jsonOut(['files' => $files, 'dir' => $dir]);
}

if ($action === 'download') {
    $id = (string) ($_GET['id'] ?? '');
    if (!receiveValidId($id)) {
        failJson('ID tidak valid.', 400);
    }
    $meta = json_decode((string) @file_get_contents(receiveMetaPath($id)), true);
    $data = $dir . '/' . $id;
    if (!is_array($meta) || !is_file($data)) {
        failJson('File tidak ditemukan.', 404);
    }
    $name = receiveSafeName((string) ($meta['name'] ?? 'file'));
    $mime = 'application/octet-stream';
    $rawMime = (string) ($meta['mime'] ?? '');
    if (preg_match('~^[a-z0-9!#$&^_.+-]+/[a-z0-9!#$&^_.+-]+$~i', $rawMime)) {
        $mime = $rawMime;
    }
    serveFile($data, $name, $mime);
}

if ($action === 'delete') {
    $id = (string) ($_POST['id'] ?? $_GET['id'] ?? '');
    if (!receiveValidId($id)) {
        failJson('ID tidak valid.', 400);
    }
    $found = is_file($dir . '/' . $id) || is_file(receiveMetaPath($id));
    if ($found) {
        @unlink($dir . '/' . $id);
        @unlink(receiveMetaPath($id));
    }
    jsonOut(['ok' => true, 'found' => $found]);
}

if ($action === 'clear') {
    $n = 0;
    foreach (glob($dir . '/*') ?: [] as $p) {
        $base = basename($p);
        if ($base === '.htaccess' || $base === '.') {
            continue;
        }
        if (is_file($p)) {
            @unlink($p);
            $n++;
        }
    }
    jsonOut(['ok' => true, 'removed' => $n]);
}

if (!isset($_FILES['files'])) {
    failJson('Tidak ada file yang dikirim.');
}
$f = $_FILES['files'];
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
if ($n > 30) {
    failJson('Maksimal 30 file per kiriman.');
}
$limit = uploadLimitBytes();
$saved = [];
for ($i = 0; $i < $n; $i++) {
    if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    if ($f['error'][$i] !== UPLOAD_ERR_OK) {
        failJson(uploadErrorText((int) $f['error'][$i]));
    }
    $size = (int) $f['size'][$i];
    if ($limit > 0 && $size > $limit) {
        failJson('File "' . receiveSafeName((string) $f['name'][$i]) . '" terlalu besar. Batas ' . humanSize($limit) . '.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (finfo_file($finfo, (string) $f['tmp_name'][$i]) ?: '') : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    $id = bin2hex(random_bytes(8));
    $target = $dir . '/' . $id;
    if (!move_uploaded_file((string) $f['tmp_name'][$i], $target)) {
        failJson('Gagal menyimpan file ke server.', 500);
    }
    $name = receiveSafeName((string) $f['name'][$i]);
    $meta = [
        'id' => $id,
        'name' => $name,
        'mime' => $mime !== '' ? $mime : 'application/octet-stream',
        'time' => time(),
    ];
    file_put_contents(receiveMetaPath($id), json_encode($meta, JSON_UNESCAPED_UNICODE), LOCK_EX);
    $saved[] = ['id' => $id, 'name' => $name, 'size' => $size];
}
jsonOut(['ok' => true, 'saved' => $saved]);
