<?php
declare(strict_types=1);

require_once __DIR__ . '/../health_functions.php';
requireGet();
$identity = requireValidAuthentication();

$results = array_map('checkService', configuredServices());
$up = count(array_filter($results, static function (array $service): bool {
    return $service['status'] === 'UP';
}));
$total = count($results);

sendJson([
    'success' => true,
    'checked_at' => gmdate('c'),
    'requested_by' => [
        'user_id' => $identity['user_id'] ?? null,
        'role' => $identity['role'] ?? null,
    ],
    'total_services' => $total,
    'services_up' => $up,
    'services_down' => $total - $up,
    'overall_status' => $up === $total ? 'UP' : 'DEGRADED',
    'services' => $results,
]);
