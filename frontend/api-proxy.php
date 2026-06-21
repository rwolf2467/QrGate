<?php
/**
 * API Proxy - Hides the API key from client-side JavaScript
 * Only allows specific safe endpoints
 */
require_once 'config.php';

header('Content-Type: application/json');

// Only allow specific safe endpoints for public access
$allowedEndpoints = [
    'payment_methods'  => '/api/show/get/payment_methods',
    'show'             => '/api/show/get',
    'stripe_pub_key'   => '/api/show/get/stripe_pub_key',
    // Used by the setup wizard: poll install state (e.g. while the backend
    // restarts) and generate a random secret key server-side.
    'setup_status'     => '/api/setup/status',
    'setup_genkey'     => '/api/setup/genkey',
];

$endpoint = $_GET['endpoint'] ?? '';

if (!isset($allowedEndpoints[$endpoint])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint']);
    exit;
}

$result = makeApiCall($allowedEndpoints[$endpoint]);
echo json_encode($result);
