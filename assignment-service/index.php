<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Request-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($path === '') {
    $path = '/';
}
$rid = $_SERVER['HTTP_X_REQUEST_ID'] ?? ('req-' . bin2hex(random_bytes(4)));

$dataFile     = __DIR__ . '/data/assignments.json';
$servicesFile = __DIR__ . '/services.json';

function sendResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function sendSuccess(int $statusCode, $data, string $rid): void
{
    sendResponse($statusCode, [
        'success' => true,
        'data'    => $data,
        'meta'    => ['request_id' => $rid],
    ]);
}

function sendError(int $statusCode, string $code, string $message, string $rid): void
{
    sendResponse($statusCode, [
        'success' => false,
        'error'   => [
            'code'    => $code,
            'message' => $message,
        ],
        'meta'    => ['request_id' => $rid],
    ]);
}

function loadConfig(string $servicesFile): array
{
    if (!file_exists($servicesFile)) {
        return ['course' => 'http://127.0.0.1:8103', 'verify_course' => false];
    }
    $cfg = json_decode(file_get_contents($servicesFile), true);
    return is_array($cfg) ? $cfg : ['course' => 'http://127.0.0.1:8103', 'verify_course' => false];
}

function loadAssignments(string $dataFile): array
{
    if (!file_exists($dataFile)) {
        if (!is_dir(dirname($dataFile))) {
            mkdir(dirname($dataFile), 0777, true);
        }
        file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    $items = json_decode(file_get_contents($dataFile), true);
    return is_array($items) ? $items : [];
}

function saveAssignments(string $dataFile, array $items): void
{
    file_put_contents($dataFile, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function verifyCourseExists(string $courseId, string $rid, array $config): array
{
    if (empty($config['verify_course'])) {
        $validDummyCourses = ['course-001', 'course-002'];
        if (!in_array($courseId, $validDummyCourses, true)) {
            return [
                'ok'      => false,
                'status'  => 404,
                'code'    => 'course_not_found',
                'message' => "Course dengan id '{$courseId}' tidak ditemukan"
            ];
        }
        return ['ok' => true];
    }

    $courseBaseUrl = rtrim($config['course'] ?? 'http://127.0.0.1:8103', '/');
    $url = $courseBaseUrl . '/api/courses';

    $context = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'timeout'       => 3.0,
            'ignore_errors' => true,
            'header'        => implode("\r\n", [
                'Accept: application/json',
                'X-Request-ID: ' . $rid,
            ]),
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        $lastErr = error_get_last()['message'] ?? '';
        if (stripos($lastErr, 'timed out') !== false) {
            return [
                'ok'      => false,
                'status'  => 504,
                'code'    => 'course_service_timeout',
                'message' => 'Course Service melebihi batas waktu tunggu (timeout)'
            ];
        }
        return [
            'ok'      => false,
            'status'  => 503,
            'code'    => 'course_service_unavailable',
            'message' => 'Course Service tidak dapat dihubungi'
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        return [
            'ok'      => false,
            'status'  => 502,
            'code'    => 'bad_gateway',
            'message' => 'Respons dari Course Service tidak valid'
        ];
    }

    $courses = $decoded['data'] ?? [];
    foreach ($courses as $course) {
        if (($course['id'] ?? '') === $courseId) {
            return ['ok' => true];
        }
    }

    return [
        'ok'      => false,
        'status'  => 404,
        'code'    => 'course_not_found',
        'message' => "Course dengan id '{$courseId}' tidak ditemukan"
    ];
}

// 1. GET /health
if ($path === '/health' && $method === 'GET') {
    sendSuccess(200, [
        'service' => 'assignment',
        'status'  => 'healthy',
    ], $rid);
}

// 2. GET /api/assignments?course_id=...
if ($path === '/api/assignments' && $method === 'GET') {
    $courseId = trim($_GET['course_id'] ?? '');
    if ($courseId === '') {
        sendError(400, 'validation_error', 'Parameter query course_id wajib diisi', $rid);
    }

    $assignments = loadAssignments($dataFile);
    $filtered = array_values(array_filter($assignments, function ($item) use ($courseId) {
        return ($item['course_id'] ?? '') === $courseId;
    }));

    sendSuccess(200, $filtered, $rid);
}

// 3. GET /api/assignments/{id}
if (preg_match('#^/api/assignments/([^/]+)$#', $path, $matches) && $method === 'GET') {
    $targetId = $matches[1];
    $assignments = loadAssignments($dataFile);
    foreach ($assignments as $item) {
        if (($item['id'] ?? '') === $targetId) {
            sendSuccess(200, $item, $rid);
        }
    }

    sendError(404, 'assignment_not_found', "Assignment dengan id '{$targetId}' tidak ditemukan", $rid);
}

// 4. POST /api/assignments
if ($path === '/api/assignments' && $method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        sendError(415, 'unsupported_media_type', 'Content-Type harus application/json', $rid);
    }

    $rawBody = file_get_contents('php://input');
    $body    = json_decode($rawBody, true);
    if (!is_array($body)) {
        sendError(400, 'invalid_json', 'Format JSON pada request body tidak valid', $rid);
    }

    $courseId    = trim((string)($body['course_id'] ?? ''));
    $title       = trim((string)($body['title'] ?? ''));
    $description = trim((string)($body['description'] ?? ''));
    $dueDate     = trim((string)($body['due_date'] ?? ''));

    if ($courseId === '' || $title === '') {
        sendError(400, 'validation_error', 'Field course_id dan title wajib diisi', $rid);
    }

    $config = loadConfig($servicesFile);
    $check  = verifyCourseExists($courseId, $rid, $config);
    if (!$check['ok']) {
        sendError($check['status'], $check['code'], $check['message'], $rid);
    }

    $assignments = loadAssignments($dataFile);
    $nextNumber  = count($assignments) + 1;
    $newId       = 'asg-' . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);

    $existingIds = array_column($assignments, 'id');
    while (in_array($newId, $existingIds, true)) {
        $nextNumber++;
        $newId = 'asg-' . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
    }

    $newAssignment = [
        'id'          => $newId,
        'course_id'   => $courseId,
        'title'       => $title,
        'description' => $description,
        'due_date'    => $dueDate !== '' ? $dueDate : date('c', strtotime('+7 days')),
        'created_at'  => date('c'),
    ];

    $assignments[] = $newAssignment;
    saveAssignments($dataFile, $assignments);

    sendSuccess(201, $newAssignment, $rid);
}

// 5. DELETE /api/assignments/{id}
if (preg_match('#^/api/assignments/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
    $targetId    = $matches[1];
    $assignments = loadAssignments($dataFile);
    $foundIndex  = null;

    foreach ($assignments as $index => $item) {
        if (($item['id'] ?? '') === $targetId) {
            $foundIndex = $index;
            break;
        }
    }

    if ($foundIndex === null) {
        sendError(404, 'assignment_not_found', "Assignment dengan id '{$targetId}' tidak ditemukan", $rid);
    }

    $deleted = $assignments[$foundIndex];
    unset($assignments[$foundIndex]);
    saveAssignments($dataFile, $assignments);

    sendSuccess(200, [
        'deleted' => true,
        'id'      => $deleted['id'],
    ], $rid);
}

sendError(404, 'not_found', 'Endpoint tidak ditemukan', $rid);