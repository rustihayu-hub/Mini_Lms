<?php
declare(strict_types=1);

const MONITORING_SERVICE_NAME = 'monitoring-service';

function sendJson(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requireGet(): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        header('Allow: GET');
        sendJson(['success' => false, 'error' => ['code' => 'method_not_allowed', 'message' => 'Endpoint ini hanya menerima metode GET.']], 405);
    }
}

function envValue(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return trim($value);
}

function timeoutSeconds(): float
{
    $value = (float) (envValue('MONITORING_TIMEOUT_SECONDS', '3') ?? '3');
    return ($value >= 0.1 && $value <= 30) ? $value : 3.0;
}

/** @return array{body:string|false,http_code:int,error:?string,timed_out:bool,duration_ms:int} */
function httpGet(string $url, array $headers = []): array
{
    $started = microtime(true);
    $timeout = timeoutSeconds();

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => (int) round($timeout * 1000),
            CURLOPT_TIMEOUT_MS => (int) round($timeout * 1000),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $result = [
            'body' => $body,
            'http_code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'error' => $body === false ? (curl_error($ch) ?: 'Permintaan HTTP gagal.') : null,
            'timed_out' => $errno === CURLE_OPERATION_TIMEDOUT,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
        curl_close($ch);
        return $result;
    }

    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'header' => implode("\r\n", array_merge(['Accept: application/json', 'Connection: close'], $headers)),
        'timeout' => $timeout,
        'ignore_errors' => true,
        'follow_location' => 0,
    ]]);
    $previous = set_error_handler(static function (): bool { return true; });
    $body = file_get_contents($url, false, $context);
    if ($previous !== null) {
        set_error_handler($previous);
    } else {
        restore_error_handler();
    }
    $responseHeaders = $http_response_header ?? [];
    $httpCode = 0;
    if (isset($responseHeaders[0]) && preg_match('/\s(\d{3})(?:\s|$)/', $responseHeaders[0], $matches)) {
        $httpCode = (int) $matches[1];
    }
    $elapsed = microtime(true) - $started;
    return [
        'body' => $body,
        'http_code' => $httpCode,
        'error' => $body === false ? ($elapsed >= $timeout * 0.9 ? 'Batas waktu permintaan terlampaui.' : 'Layanan tidak dapat dihubungi.') : null,
        'timed_out' => $body === false && $elapsed >= $timeout * 0.9,
        'duration_ms' => (int) round($elapsed * 1000),
    ];
}

function bearerHeader(): string
{
    $header = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
    if (!preg_match('/^Bearer\s+\S+$/i', $header)) {
        header('WWW-Authenticate: Bearer');
        sendJson(['success' => false, 'error' => ['code' => 'missing_token', 'message' => 'Kirim token melalui header Authorization: Bearer <token>.']], 401);
    }
    return $header;
}

function requireValidAuthentication(): array
{
    $authorization = bearerHeader();
    $authBaseUrl = envValue('AUTH_SERVICE_URL');
    if ($authBaseUrl === null || !filter_var($authBaseUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $authBaseUrl)) {
        sendJson(['success' => false, 'error' => ['code' => 'auth_service_not_configured', 'message' => 'AUTH_SERVICE_URL belum dikonfigurasi dengan URL HTTP/HTTPS yang valid.']], 503);
    }

    $response = httpGet(rtrim($authBaseUrl, '/') . '/api/auth/validate', ['Authorization: ' . $authorization]);
    if ($response['timed_out'] || $response['body'] === false || $response['http_code'] === 0 || $response['http_code'] >= 500) {
        sendJson(['success' => false, 'error' => ['code' => 'auth_service_unavailable', 'message' => 'Validasi token tidak dapat dilakukan karena Auth Service tidak tersedia.']], 503);
    }
    if ($response['http_code'] < 200 || $response['http_code'] >= 300) {
        header('WWW-Authenticate: Bearer');
        sendJson(['success' => false, 'error' => ['code' => 'invalid_token', 'message' => 'Token ditolak oleh Auth Service.']], 401);
    }

    $payload = json_decode((string) $response['body'], true);
    if (!is_array($payload) || ($payload['data']['valid'] ?? false) !== true) {
        sendJson(['success' => false, 'error' => ['code' => 'invalid_auth_response', 'message' => 'Respons validasi dari Auth Service tidak sah.']], 502);
    }
    return $payload['data'];
}

function configuredServices(): array
{
    return [
        ['name' => 'auth', 'url_env' => 'AUTH_SERVICE_URL', 'path_env' => 'AUTH_HEALTH_PATH', 'default_path' => '/health'],
        ['name' => 'course', 'url_env' => 'COURSE_SERVICE_URL', 'path_env' => 'COURSE_HEALTH_PATH', 'default_path' => '/api/health'],
        ['name' => 'assignment', 'url_env' => 'ASSIGNMENT_SERVICE_URL', 'path_env' => 'ASSIGNMENT_HEALTH_PATH', 'default_path' => '/health'],
        ['name' => 'submission', 'url_env' => 'SUBMISSION_SERVICE_URL', 'path_env' => 'SUBMISSION_HEALTH_PATH', 'default_path' => '/health'],
        ['name' => 'notification', 'url_env' => 'NOTIFICATION_SERVICE_URL', 'path_env' => 'NOTIFICATION_HEALTH_PATH', 'default_path' => '/api.php/health'],
    ];
}

function checkService(array $service): array
{
    $baseUrl = envValue($service['url_env']);
    $path = envValue($service['path_env'], $service['default_path']) ?? $service['default_path'];
    if ($baseUrl === null || !filter_var($baseUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $baseUrl)) {
        return ['name' => $service['name'], 'status' => 'DOWN', 'reason' => 'configuration_error', 'message' => $service['url_env'] . ' belum diisi dengan URL HTTP/HTTPS yang valid.'];
    }
    if ($path === '' || $path[0] !== '/') {
        return ['name' => $service['name'], 'status' => 'DOWN', 'reason' => 'configuration_error', 'message' => $service['path_env'] . ' harus diawali garis miring (/).'];
    }

    $healthUrl = rtrim($baseUrl, '/') . $path;
    $response = httpGet($healthUrl);
    $isUp = $response['body'] !== false && $response['http_code'] >= 200 && $response['http_code'] < 300;
    $result = [
        'name' => $service['name'],
        'status' => $isUp ? 'UP' : 'DOWN',
        'health_url' => $healthUrl,
        'http_code' => $response['http_code'],
        'duration_ms' => $response['duration_ms'],
    ];
    if (!$isUp) {
        $result['reason'] = $response['timed_out'] ? 'timeout' : ($response['http_code'] > 0 ? 'non_success_response' : 'connection_error');
        $result['message'] = $response['error'] ?? 'Endpoint health mengembalikan HTTP ' . $response['http_code'] . '.';
    }
    return $result;
}
