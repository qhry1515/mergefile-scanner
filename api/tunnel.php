<?php
declare(strict_types=1);
require __DIR__ . '/../lib/helpers.php';
apiBootstrap();
session_start();

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'status');
$active = tunnelActive();
$state = $active ? readTunnelState() : null;

function tunnelGuard(?array $state): void
{
    if (!$state) {
        return;
    }
    if (!tunnelAuthed($state)) {
        failJson(!empty($state['locked']) ? 'Link terkunci (terlalu banyak percobaan PIN salah). Buat link baru di laptop.' : 'PIN diperlukan.', 401);
    }
}

if ($action === 'status') {
    tunnelGuard($state);
    if (!$active) {
        jsonOut(['active' => false]);
    }
    jsonOut([
        'active' => true,
        'url' => $state['url'],
        'pin' => isTrustedLocalRequest() ? $state['pin'] : null,
        'key' => isTrustedLocalRequest() ? (string) ($state['key'] ?? '') : null,
        'locked' => !empty($state['locked']),
        'fails' => isTrustedLocalRequest() ? (int) ($state['fails'] ?? 0) : null,
    ]);
}

if ($action === 'stop') {
    tunnelGuard($state);
    if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
        @exec('taskkill /F /IM cloudflared.exe 2>NUL');
    }
    clearTunnelState();
    jsonOut(['ok' => true, 'active' => false]);
}

if ($action === 'start') {
    tunnelGuard($state);
    if ($active) {
        jsonOut(['active' => true, 'url' => $state['url'], 'pin' => $state['pin'], 'key' => (string) ($state['key'] ?? '')]);
    }
    if (!is_file(cloudflaredPath())) {
        failJson('cloudflared belum ada di folder bin/.', 500);
    }
    if (cloudflaredRunning()) {
        @exec('taskkill /F /IM cloudflared.exe 2>NUL');
        usleep(600000);
    }
    clearTunnelState();

    $port = (int) ($_SERVER['SERVER_PORT'] ?? 80);
    $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
    $appDirPath = str_replace('\\', '/', dirname(__DIR__));
    $base = '';
    if ($docRoot !== '' && stripos($appDirPath . '/', $docRoot . '/') === 0) {
        $base = rtrim(substr($appDirPath, strlen($docRoot)), '/');
    }
    $origin = 'http://127.0.0.1:' . $port . $base . '/';

    $outLog = appDir('config.tunnel.out.log');
    $errLog = tunnelLogPath();
    @unlink($outLog);
    @unlink($errLog);

    $psBin = 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
    if (!is_file($psBin)) {
        $psBin = 'powershell.exe';
    }
    $psScript = 'Start-Process -FilePath \'' . cloudflaredPath() .
        '\' -ArgumentList \'tunnel\',\'--url\',\'' . $origin . '\',\'--no-autoupdate\'' .
        ' -RedirectStandardOutput \'' . $outLog . '\' -RedirectStandardError \'' . $errLog .
        '\' -WindowStyle Hidden';
    $spec = [
        0 => ['file', 'NUL', 'r'],
        1 => ['file', 'NUL', 'w'],
        2 => ['file', 'NUL', 'w'],
    ];
    $pipes = [];
    $proc = @proc_open([$psBin, '-NoProfile', '-Command', $psScript], $spec, $pipes);
    if (is_resource($proc)) {
        proc_close($proc);
    }

    $url = '';
    for ($i = 0; $i < 60; $i++) {
        clearstatcache();
        $text = '';
        foreach ([$errLog, $outLog] as $lp) {
            if (is_file($lp)) {
                $text .= (string) @file_get_contents($lp);
            }
        }
        if (preg_match('~https://[a-z0-9-]+\.trycloudflare\.com~i', $text, $m)) {
            $url = $m[0];
            break;
        }
        if (!cloudflaredRunning() && $i > 10) {
            break;
        }
        usleep(250000);
    }

    if ($url === '') {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            @exec('taskkill /F /IM cloudflared.exe 2>NUL');
        }
        clearTunnelState();
        failJson('Gagal membuat link internet. Pastikan laptop terhubung internet, lalu coba lagi.', 500);
    }

    $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $key = bin2hex(random_bytes(16));
    writeTunnelState([
        'url' => $url,
        'pin' => $pin,
        'key' => $key,
        'fails' => 0,
        'locked' => false,
        'origin' => $origin,
        'started' => time(),
    ]);
    jsonOut(['active' => true, 'url' => $url, 'pin' => $pin, 'key' => $key]);
}

failJson('Aksi tidak dikenal.', 400);
