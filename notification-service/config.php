<?php
// config.php : satu-satunya tempat pengaturan service.
// Alamat service dibaca dari services.json (tidak ditulis ulang di kode).

$servicesFile = __DIR__ . '/services.json';
$services = is_file($servicesFile)
    ? json_decode(file_get_contents($servicesFile), true)
    : null;

if (!is_array($services)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => [
            'code'    => 'config_error',
            'message' => 'services.json tidak ditemukan atau bukan JSON yang valid',
        ],
        'meta'    => ['request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? 'req-config'],
    ]);
    exit;
}

return [
    'services'           => $services,
    'auth_url'           => $services['auth'] ?? 'http://127.0.0.1:8102',
    // Sesuaikan dengan API_CONTRACT kelompok Authentication.
    'auth_validate_path' => getenv('AUTH_VALIDATE_PATH') ?: '/api/auth/me',
    // 'remote' (default) atau 'format_only' (hanya untuk uji mandiri).
    'auth_mode'          => getenv('AUTH_MODE') ?: 'remote',
    // Batas waktu pemanggilan antar-service, dalam detik.
    'timeout'            => (int) (getenv('HTTP_TIMEOUT') ?: 3),
    'data_file'          => __DIR__ . '/data/notifications.json',
];
