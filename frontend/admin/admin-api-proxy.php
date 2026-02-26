<?php
/**
 * Admin API Proxy - Hides the API key from client-side JavaScript
 * Requires admin session
 */
require_once '../config.php';

// Check admin authentication
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Allowed endpoints for admin
$allowedEndpoints = [
    'stats' => '/api/stats',
    'show' => '/api/show/get',
    'show_edit' => '/api/show/edit',
    'images' => '/api/image/current',
    'cast_image' => '/api/show/cast/image/'
];

$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if (!isset($allowedEndpoints[$endpoint])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint']);
    exit;
}

if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $result = makeApiCall($allowedEndpoints[$endpoint], 'POST', $data);
} else {
    $result = makeApiCall($allowedEndpoints[$endpoint]);
}

echo json_encode($result);
