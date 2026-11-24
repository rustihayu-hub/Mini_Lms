<?php
// index.php : Notification Service (Mini LMS Terdistribusi), port 8106
//
// Endpoint:
//   GET   /health
//   GET   /api/notifications?user_id=...
//   POST  /api/notifications
//   PATCH /api/notifications/{id}/read
//
// Jalankan:  php -S 127.0.0.1:8106 index.php

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';

$method    = $_SERVER['REQUEST_METHOD'];
$path      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path      = ($path !== '/') ? rtrim($path, '/') : '/';
$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid('req-');

header('X-Request-ID: ' . $requestId);

// ==========================================================
// 1. FUNGSI RESPONSE (format sesuai kontrak API bersama)
// ==========================================================

function sendSuccess(int $status, $data): void
{
    global $requestId;
    http_response_code($status);
    echo json_encode([
        'success' => true,
        'data'    => $data,
        'meta'    => ['request_id' => $requestId],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sendError(int $status, string $code, string $message): void
{
    global $requestId;
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'error'   => ['code' => $code, 'message' => $message],
        'meta'    => ['request_id' => $requestId],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ==========================================================
// 2. PENYIMPANAN DATA (file JSON dengan kunci agar aman)
// ==========================================================

/**
 * Baca-ubah-tulis data secara atomik. $fn menerima array data (by reference)
 * dan boleh mengubahnya; nilai kembalian $fn dikembalikan ke pemanggil.
 */
function mutateData(callable $fn)
{
    global $config;
    $file = $config['data_file'];

    if (!is_dir(dirname($file))) {
        @mkdir(dirname($file), 0777, true);
    }

    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        sendError(500, 'storage_error', 'Penyimpanan data tidak dapat dibuka');
    }

    flock($fp, LOCK_EX);
    $raw  = stream_get_contents($fp);
    $data = ($raw === '' || $raw === false) ? [] : json_decode($raw, true);

    if (!is_array($data)) {
        // Jangan menimpa berkas yang rusak: lebih baik gagal daripada menghapus data.
        flock($fp, LOCK_UN);
        fclose($fp);
        sendError(500, 'storage_error', 'Isi data/notifications.json tidak valid');
    }

    $result = $fn($data);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $result;
}

function readData(): array
{
    global $config;
    $file = $config['data_file'];

    if (!is_file($file)) {
        return [];
    }
    $fp = @fopen($file, 'r');
    if ($fp === false) {
        sendError(500, 'storage_error', 'Penyimpanan data tidak dapat dibuka');
    }
    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = ($raw === '' || $raw === false) ? [] : json_decode($raw, true);
    if (!is_array($data)) {
        sendError(500, 'storage_error', 'Isi data/notifications.json tidak valid');
    }
    return $data;
}

function nextId(array $data): string
{
    $max = 0;
    foreach ($data as $item) {
        if (isset($item['id']) && preg_match('/^notif-(\d+)$/', $item['id'], $m)) {
            $max = max($max, (int) $m[1]);
        }
    }
    return 'notif-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
}

// ==========================================================
// 4. ROUTING
// ==========================================================

// ---------- GET /health (tanpa token) ----------
if ($path === '/health') {
    if ($method !== 'GET') {
        sendError(404, 'not_found', 'Endpoint tidak ditemukan');
    }
    sendSuccess(200, ['service' => 'notification', 'status' => 'healthy']);
}

// ---------- GET /api/notifications?user_id=... ----------
if ($path === '/api/notifications' && $method === 'GET') {

    $userId = trim((string) ($_GET['user_id'] ?? ''));
    if ($userId === '') {
        sendError(400, 'validation_error', 'Parameter user_id wajib diisi');
    }

    $result = array_values(array_filter(
        readData(),
        fn($item) => ($item['user_id'] ?? null) === $userId
    ));

    sendSuccess(200, $result);
}

// ---------- POST /api/notifications ----------
if ($path === '/api/notifications' && $method === 'POST') {

    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        sendError(415, 'unsupported_media_type', 'Content-Type harus application/json');
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        sendError(400, 'invalid_json', 'Isi request bukan JSON yang valid');
    }

    $userId  = $body['user_id'] ?? null;
    $message = $body['message'] ?? null;
    $type    = $body['type'] ?? 'general';
    $subId   = $body['submission_id'] ?? null;

    if (!is_string($userId) || trim($userId) === '' ||
        !is_string($message) || trim($message) === '') {
        sendError(400, 'validation_error', 'Field user_id dan message wajib diisi (teks tidak kosong)');
    }
    if (!is_string($type) || trim($type) === '') {
        sendError(400, 'validation_error', 'Field type harus berupa teks');
    }
    if ($subId !== null && !is_string($subId)) {
        sendError(400, 'validation_error', 'Field submission_id harus berupa teks');
    }
    $length = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);
    if ($length > 500) {
        sendError(400, 'validation_error', 'Field message maksimal 500 karakter');
    }

    $new = mutateData(function (array &$data) use ($userId, $message, $type, $subId) {
        $item = [
            'id'            => nextId($data),
            'user_id'       => trim($userId),
            'message'       => trim($message),
            'type'          => trim($type),
            'submission_id' => $subId,
            'is_read'       => false,
            'created_at'    => date('c'),
        ];
        $data[] = $item;
        return $item;
    });

    sendSuccess(201, $new);
}

// ---------- PATCH /api/notifications/{id}/read ----------
if (preg_match('#^/api/notifications/([^/]+)/read$#', $path, $m) && $method === 'PATCH') {

    $notificationId = urldecode($m[1]);

    $updated = mutateData(function (array &$data) use ($notificationId) {
        foreach ($data as &$item) {
            if (($item['id'] ?? null) === $notificationId) {
                $item['is_read'] = true;
                return $item;
            }
        }
        return null;
    });

    if ($updated === null) {
        sendError(404, 'notification_not_found', 'Notifikasi tidak ditemukan');
    }

    sendSuccess(200, $updated);
}

// ---------- Tidak dikenal ----------
sendError(404, 'not_found', 'Endpoint tidak ditemukan');
