<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$rid = $_SERVER['HTTP_X_REQUEST_ID'] ?? 'req-001';

if (($uri === '/' || $uri === '/index.html') && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($accept, 'text/html') || $uri === '/index.html' || !str_contains($accept, 'application/json')) {
        header('Content-Type: text/html; charset=utf-8');
        readfile(__DIR__ . '/index.html');
        exit;
    }
}
require_once __DIR__ . '/health_functions.php';

if ($uri === '/health') {
    require __DIR__ . '/api/health.php';
    exit;
}

// --- TAMBAHAN BARU: Mendukung URL resmi modul (/api/monitoring/services) sekaligus URL lama ---
if ($uri === '/api/monitoring/services' || $uri === '/api/health/all' || $uri === '/all') {
    require __DIR__ . '/api/health_all.php';
    exit;
}

// --- TAMBAHAN BARU: Endpoint ke-3 wajib sesuai modul (/api/monitoring/events) ---
if ($uri === '/api/monitoring/events') {
    require __DIR__ . '/api/events.php';
    exit;
}

// --- PENYESUAIAN KONTRAK: Format error 404 sesuai aturan modul (success: false) ---
header('Content-Type: application/json');
http_response_code(404);
echo json_encode([
    'success' => false,
    'error'   => [
        'code'    => 'not_found',
        'message' => "Endpoint {$uri} tidak ditemukan"
    ],
    'meta'    => ['request_id' => $rid]
]);