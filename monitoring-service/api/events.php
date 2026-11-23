<?php
header('Content-Type: application/json');
$rid = $_SERVER['HTTP_X_REQUEST_ID'] ?? 'req-' . uniqid();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Pastikan folder data/ tersedia
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}
$logFile = $dataDir . '/events.json';
$events = file_exists($logFile) ? (json_decode(file_get_contents($logFile), true) ?? []) : [];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || empty($input['service']) || empty($input['status'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => 'invalid_input',
                'message' => 'Field service dan status wajib diisi dalam JSON.'
            ],
            'meta'    => ['request_id' => $rid]
        ]);
        exit;
    }

    $newEvent = [
        'id'         => 'evt-' . str_pad(count($events) + 1, 3, '0', STR_PAD_LEFT),
        'service'    => $input['service'],
        'status'     => $input['status'],
        'message'    => $input['message'] ?? 'Pemeriksaan status service dicatat',
        'created_at' => date('c')
    ];

    $events[] = $newEvent;
    file_put_contents($logFile, json_encode($events, JSON_PRETTY_PRINT));

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data'    => $newEvent,
        'meta'    => ['request_id' => $rid]
    ]);
    exit;
}

if ($method === 'GET') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => $events,
        'meta'    => ['request_id' => $rid]
    ]);
    exit;
}

http_response_code(405);
echo json_encode([
    'success' => false,
    'error'   => ['code' => 'method_not_allowed', 'message' => 'Gunakan metode POST atau GET'],
    'meta'    => ['request_id' => $rid]
]);