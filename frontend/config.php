<?php
// Do not expose PHP errors (paths, stack traces, secrets) to visitors.
// Errors are still logged server-side via error_log().
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Harden session cookies (httponly, secure, samesite) before the session starts.
$cookieParams = [
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
];
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $cookieParams['secure'] = true;
}
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
} else {
    session_set_cookie_params(
        $cookieParams['lifetime'],
        $cookieParams['path'] . '; samesite=' . $cookieParams['samesite'],
        '',
        $cookieParams['secure'] ?? false,
        $cookieParams['httponly']
    );
}

session_start();

// Session idle/absolute timeout for authenticated (admin/ticketflow/handheld)
// sessions. Anonymous public visitors are NOT logged out mid-purchase: the
// timeout only applies once an auth flag is present in the session.
define('SESSION_IDLE_TIMEOUT', 30 * 60);      // 30 minutes of inactivity
define('SESSION_ABSOLUTE_TIMEOUT', 12 * 3600); // 12 hours hard cap

// Auth flags actually set by admin/login.php on successful login.
$_sessionIsAuthenticated = !empty($_SESSION['admin'])
    || !empty($_SESSION['ticketflow_access'])
    || !empty($_SESSION['handheld_access']);

if ($_sessionIsAuthenticated) {
    $now = time();
    $idleExpired = isset($_SESSION['last_activity'])
        && ($now - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT;
    $absoluteExpired = isset($_SESSION['login_time'])
        && ($now - $_SESSION['login_time']) > SESSION_ABSOLUTE_TIMEOUT;

    if ($idleExpired || $absoluteExpired) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Your session has expired. Please log in again.';
    } else {
        if (empty($_SESSION['login_time'])) {
            $_SESSION['login_time'] = $now;
        }
        $_SESSION['last_activity'] = $now;
    }
}

define('API_KEY', 'YourGeneratedKeyHere');
define('API_BASE_URL', 'https://qrgate-backend.example.com/');
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
        // Never let a slow/hung backend freeze the PHP worker indefinitely.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

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
        // Log the technical detail (HTTP code / cURL string) server-side only.
        // Do NOT leak it to visitors and do NOT set $_SESSION['error'] here:
        // the caller surfaces a single clean friendly message for the null case.
        error_log('getShows() failed: ' . $shows['error']);
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
