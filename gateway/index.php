<?php
// index.php : API Gateway Kelompok 5
header('Content-Type: application/json');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rid  = $_SERVER['HTTP_X_REQUEST_ID'] ?? 'req-001';

// 1. GET /health
if ($path === '/health' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => ['service' => 'gateway', 'status' => 'healthy'],
        'meta'    => ['request_id' => $rid],
    ]);
    exit;
}

// 2. GET /api/services
if ($path === '/api/services' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $services = json_decode(file_get_contents(__DIR__ . '/services.json'), true);
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => $services,
        'meta'    => ['request_id' => $rid],
    ]);
    exit;
}

// 3. ANY /api/{service}/{path}
// Peta nama resource -> nama service di services.json
$routes = [
    'auth'          => 'auth',
    'courses'       => 'course',
    'assignments'   => 'assignment',
    'submissions'   => 'submission',
    'notifications' => 'notification',
    'monitoring'    => 'monitoring',
];

// Path yang boleh diakses tanpa token
$publicPaths = ['/api/auth/login', '/api/auth/register'];

if (strpos($path, '/api/') === 0) {
    $segments = explode('/', trim($path, '/'));
    $resource = $segments[1] ?? '';
    $services = json_decode(file_get_contents(__DIR__ . '/services.json'), true);

    if (!isset($routes[$resource]) || !isset($services[$routes[$resource]])) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error'   => ['code' => 'service_not_found', 'message' => 'Service tidak ditemukan'],
            'meta'    => ['request_id' => $rid],
        ]);
        exit;
    }

    // Pemeriksaan token awal: harus ada header "Authorization: Bearer <token>"
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!in_array($path, $publicPaths, true) && !preg_match('/^Bearer\s+\S+$/', $auth)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error'   => ['code' => 'unauthorized', 'message' => 'Token tidak ada atau tidak valid'],
            'meta'    => ['request_id' => $rid],
        ]);
        exit;
    }

    // Validasi body JSON untuk POST, PUT, PATCH (berkas multipart tidak diperiksa)
    $input = file_get_contents('php://input');
    $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'], true)
        && $input !== ''
        && stripos($ctype, 'application/json') === 0) {
        json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => ['code' => 'invalid_json', 'message' => 'Isi request bukan JSON yang valid'],
                'meta'    => ['request_id' => $rid],
            ]);
            exit;
        }
    }

    // Susun alamat tujuan
    $url = $services[$routes[$resource]] . $path;
    if (!empty($_SERVER['QUERY_STRING'])) {
        $url .= '?' . $_SERVER['QUERY_STRING'];
    }

    // Header yang diteruskan
    $headers = ['X-Request-ID: ' . $rid, 'Accept: application/json'];
    if (!empty($_SERVER['CONTENT_TYPE'])) {
        $headers[] = 'Content-Type: ' . $_SERVER['CONTENT_TYPE'];
    }
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
    }

    // Teruskan request dengan cURL
    $options = [
        CURLOPT_CUSTOMREQUEST  => $_SERVER['REQUEST_METHOD'],
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 5,
    ];
    if ($input !== '') {
        $options[CURLOPT_POSTFIELDS] = $input;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $body   = curl_exec($ch);
    $errno  = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    // Service tidak menjawab
    if ($errno !== 0) {
        $timeout = ($errno === 28);
        http_response_code($timeout ? 504 : 503);
        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => $timeout ? 'gateway_timeout' : 'service_unavailable',
                'message' => $timeout ? 'Service terlalu lama menjawab' : 'Service tidak dapat dihubungi',
            ],
            'meta'    => ['request_id' => $rid],
        ]);
        exit;
    }

    // Balasan service bukan JSON yang valid
    if (!is_array(json_decode($body, true))) {
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'error'   => ['code' => 'bad_gateway', 'message' => 'Respons service tidak valid'],
            'meta'    => ['request_id' => $rid],
        ]);
        exit;
    }

    // Sukses: teruskan status dan isi apa adanya
    http_response_code($status);
    echo $body;
    exit;
}

// Selain itu: 404
http_response_code(404);
echo json_encode([
    'success' => false,
    'error'   => ['code' => 'not_found', 'message' => 'Endpoint tidak ditemukan'],
    'meta'    => ['request_id' => $rid],
]);