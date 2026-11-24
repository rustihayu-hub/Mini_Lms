<?php
declare(strict_types=1);

require_once __DIR__ . '/../health_functions.php';
requireGet();

$results = array_map('checkService', configuredServices());
$up = count(array_filter($results, static function (array $service): bool {
    return $service['status'] === 'UP';
}));
$total = count($results);

sendJson([
    'success' => true,
    'checked_at' => gmdate('c'),
    'total_services' => $total,
    'services_up' => $up,
    'services_down' => $total - $up,
    'overall_status' => $up === $total ? 'UP' : 'DEGRADED',
    'services' => $results,
]);
