<?php
/**
 * Streams a SQLite database backup from the backend to the admin as a file
 * download. Requires an authenticated admin session; the backend API key never
 * reaches the browser (the curl call adds it server-side). Read-only (GET), so
 * no CSRF token is required.
 */
require_once '../config.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$ch = curl_init(API_BASE_URL . '/api/admin/backup');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . API_KEY]);

$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ok = ($body !== false && $httpCode === 200);
curl_close($ch);

if (!$ok) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Backup failed (backend returned ' . $httpCode . ')']);
    exit;
}

$filename = 'qrgate-backup-' . date('Ymd-His') . '.db';
header('Content-Type: application/x-sqlite3');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($body));
echo $body;
