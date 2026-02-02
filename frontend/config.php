<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

define('API_KEY', 'YourGeneratedKeyHere');
define('API_BASE_URL', 'https://qrgate-backend.example.com/');
define('PAYPAL_CLIENT_ID', 'YourPayPalClientIdHere');
define('PAYPAL_CLIENT_SECRET', 'YourPayPalClientSecretHere');
define('PAYPAL_MODE', 'sandbox');
define('ORIGIN_URL', 'https://qrgate.avocloud.net/');

// Admin passwords - CHANGE THESE IN PRODUCTION!
define('ADMIN_PASSWORD', 'admin123');
define('TICKETFLOW_PASSWORD', 'ticketflow123');
define('HANDHELD_PASSWORD', 'handheld123');

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}


function makeApiCall($endpoint, $method = 'GET', $data = null)
{
    try {
        $ch = curl_init(API_BASE_URL . $endpoint);
        if ($ch === false) {
            throw new Exception('Failed to initialize CURL');
        }

        $headers = [
            'Authorization: ' . API_KEY,
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('API call failed: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('API returned error code: ' . $httpCode);
        }

        return json_decode($response, true);
    } catch (Exception $e) {
        error_log('API Error: ' . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

function getShows()
{
    $shows = makeApiCall('/api/show/get');
    if (isset($shows['error'])) {
        $_SESSION['error'] = 'Fehler beim Laden der Shows: ' . $shows['error'];
        return null;
    }
    return $shows;
}

function updateShow($data)
{
    
    if (isset($data['store_lock'])) {
        $data['store_lock'] = filter_var($data['store_lock'], FILTER_VALIDATE_BOOLEAN);
    }
    
    return makeApiCall('/api/show/edit', 'POST', $data);
}
