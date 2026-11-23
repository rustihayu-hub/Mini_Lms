<?php
declare(strict_types=1);

require_once __DIR__ . '/../health_functions.php';
requireGet();

sendJson([
    'success' => true,
    'service' => MONITORING_SERVICE_NAME,
    'status' => 'UP',
    'checked_at' => gmdate('c'),
]);
