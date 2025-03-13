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
    echo "<script>console.log('Shows: " . json_encode($shows) . "');</script>";
    return $shows;
}
