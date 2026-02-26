<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ORIGIN_URL);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_stats':
        getStats();
        break;
    case 'update_show':
        updateShowData();
        break;
    case 'add_day':
        addDay();
        break;
    case 'update_day':
        updateDay();
        break;
    case 'delete_day':
        deleteDay();
        break;
    case 'upload_image':
        uploadImage();
        break;
    case 'get_current_images':
        getCurrentImages();
        break;
    case 'save_screens':
        saveScreens();
        break;
    case 'upload_cast_image':
        uploadCastImage();
        break;
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

function getStats() {
    $shows = getShows();
    if (!$shows) {
        echo json_encode(['status' => 'error', 'message' => 'Could not load shows']);
        return;
    }

    
    $totalTickets = 0;
    $totalAvailable = 0;
    $totalSold = 0;
    $totalIncome = 0;
    $soldByDate = [];
    $availableByDate = [];
    
    foreach ($shows['dates'] as $dateId => $dateData) {
        $totalTickets += $dateData['tickets'];
        $totalAvailable += $dateData['tickets_available'];
        $sold = $dateData['tickets'] - $dateData['tickets_available'];
        $totalSold += $sold;
        $totalIncome += $sold * floatval($dateData['price']);
        
        $soldByDate[$dateData['date']] = $sold;
        $availableByDate[$dateData['date']] = $dateData['tickets_available'];
    }
    
    $stats = [
        'totalTickets' => $totalTickets,
        'totalAvailable' => $totalAvailable,
        'totalSold' => $totalSold,
        'totalIncome' => $totalIncome,
        'soldByDate' => $soldByDate,
        'availableByDate' => $availableByDate
    ];

    echo json_encode(['status' => 'success', 'data' => $stats]);
}

function updateShowData() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    
    if (isset($input['store_lock'])) {
        $input['store_lock'] = filter_var($input['store_lock'], FILTER_VALIDATE_BOOLEAN);
    }

    $result = updateShow($input);
    
    if (isset($result['status']) && $result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Show updated successfully']);
    } else {
        http_response_code(500);
        $message = $result['message'] ?? 'Failed to update show';
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
}

function uploadImage() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        return;
    }

    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
        return;
    }

    $file = $_FILES['file'];
    $type = $_POST['type'] ?? 'banner';

    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $detectedType = mime_content_type($file['tmp_name']);
    
    if (!in_array($detectedType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type: ' . $detectedType]);
        return;
    }

    
    $maxFileSize = 5 * 1024 * 1024; 
    if ($file['size'] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'File size exceeds 5MB limit']);
        return;
    }

    
    $result = uploadImageToBackend($file, $type);
    
    if (isset($result['status']) && $result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Image uploaded successfully']);
    } else {
        http_response_code(500);
        $message = $result['message'] ?? 'Failed to upload image';
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
}

function uploadImageToBackend($file, $type) {
    try {
        $ch = curl_init(API_BASE_URL . '/api/image/upload');
        if ($ch === false) {
            throw new Exception('Failed to initialize CURL');
        }

        $headers = [
            'Authorization: ' . API_KEY,
        ];

        
        if (class_exists('CURLFile')) {
            $postFields = [
                'type' => $type,
                'file' => new CURLFile($file['tmp_name'], $detectedType, $file['name'])
            ];
        } else {
            
            $postFields = [
                'type' => $type,
                'file' => '@' . $file['tmp_name'] . ';type=' . $detectedType . ';filename=' . $file['name']
            ];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('API call failed: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            
            $errorResponse = json_decode($response, true);
            $errorMessage = $errorResponse['message'] ?? 'API returned error code: ' . $httpCode;
            throw new Exception($errorMessage);
        }

        return json_decode($response, true);
    } catch (Exception $e) {
        error_log('Image upload error: ' . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}


if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}


if (!function_exists('mime_content_type')) {
    function mime_content_type($filename) {
        $mime_types = [
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            
            
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'webp' => 'image/webp',
            
            
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            
            
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            
            
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            
            
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            
            
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        
        return 'application/octet-stream';
    }
}

function getCurrentImages() {
    
    $result = makeApiCall('/api/image/current');
    
    if (isset($result['status']) && $result['status'] === 'success') {
        
        $images = $result['images'];
        foreach ($images as $key => $image) {
            if (!empty($image) && !str_starts_with($image, 'http')) {
                $images[$key] = API_BASE_URL . $image;
            }
        }
        echo json_encode(['status' => 'success', 'images' => $images]);
    } else {
        http_response_code(500);
        $message = $result['message'] ?? 'Failed to get current images';
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
}

function addDay() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['date']) || !isset($input['time']) || !isset($input['tickets']) || !isset($input['price'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        return;
    }

    
    $shows = getShows();
    if (!$shows) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not load shows']);
        return;
    }

    
    $newDateId = $input['dateId'] ?? null;
    if (empty($newDateId)) {
        
        $existingIds = array_keys($shows['dates'] ?? []);
        if (empty($existingIds)) {
            $newDateId = 'day_' . time();
        } else {
            
            $timestamps = [];
            foreach ($existingIds as $id) {
                if (strpos($id, 'day_') === 0) {
                    $timestamps[] = (int)substr($id, 4);
                }
            }
            $newDateId = 'day_' . (max($timestamps) + 1);
        }
    }
    
    
    if (isset($shows['dates'][$newDateId])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Date ID already exists']);
        return;
    }
    
    
    $shows['dates'][$newDateId] = [
        'date' => $input['date'],
        'time' => $input['time'],
        'tickets' => (int)$input['tickets'],
        'tickets_available' => (int)$input['tickets'],
        'price' => (string)$input['price']
    ];

    
    $result = updateShow($shows);
    
    if (isset($result['status']) && $result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Day added successfully', 'dateId' => $newDateId]);
    } else {
        http_response_code(500);
        $message = $result['message'] ?? 'Failed to add day';
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
}

function updateDay() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['dateId']) || !isset($input['date']) || !isset($input['time']) || !isset($input['tickets']) || !isset($input['available']) || !isset($input['price'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        return;
    }
    
    
    error_log('updateDay input: ' . print_r($input, true));

    
    $shows = getShows();
    if (!$shows) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not load shows']);
        return;
    }

    
    if (!isset($shows['dates'][$input['dateId']])) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Day not found']);
        return;
    }

    $shows['dates'][$input['dateId']] = [
        'date' => $input['date'],
        'time' => $input['time'],
        'tickets' => (int)$input['tickets'],
        'tickets_available' => (int)$input['available'],
        'price' => (string)$input['price']
    ];

    
    $result = updateShow($shows);
    
    if (isset($result['status']) && $result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Day updated successfully']);
    } else {
        http_response_code(500);
        $message = $result['message'] ?? 'Failed to update day';
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
}

function deleteDay() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    
    error_log('deleteDay input: ' . print_r($input, true));
    
    if (!$input || !isset($input['dateId'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing dateId']);
        return;
    }

    
    $shows = getShows();
    if (!$shows) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not load shows']);
        return;
    }

    
    if (!isset($shows['dates'][$input['dateId']])) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Day not found']);
        return;
    }

    unset($shows['dates'][$input['dateId']]);

    
    $result = updateShow($shows);
    
    
    error_log('deleteDay result: ' . print_r($result, true));
    
    if (isset($result['status']) && $result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Day deleted successfully']);
    } else {
        http_response_code(500);
        $message = $result['message'] ?? 'Failed to delete day';
        error_log('deleteDay error: ' . $message);
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
}

function saveScreens() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['screens'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing screens data']);
        return;
    }

    $shows = getShows();
    if (!$shows) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not load shows']);
        return;
    }

    $shows['screens'] = $input['screens'];
    $result = updateShow($shows);

    if (isset($result['status']) && $result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'Screens saved successfully']);
    } else {
        http_response_code(500);
        $message = $result['message'] ?? 'Failed to save screens';
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
}

function uploadCastImage() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        return;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
        return;
    }

    $file = $_FILES['file'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $detectedType = mime_content_type($file['tmp_name']);

    if (!in_array($detectedType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type: ' . $detectedType]);
        return;
    }

    $maxFileSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'File size exceeds 5MB limit']);
        return;
    }

    try {
        $ch = curl_init(API_BASE_URL . '/api/show/cast/upload');
        if ($ch === false) {
            throw new Exception('Failed to initialize CURL');
        }

        $headers = [
            'Authorization: ' . API_KEY,
        ];

        $postFields = [
            'file' => new CURLFile($file['tmp_name'], $detectedType, $file['name'])
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('API call failed: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorResponse = json_decode($response, true);
            $errorMessage = $errorResponse['message'] ?? 'API returned error code: ' . $httpCode;
            throw new Exception($errorMessage);
        }

        $result = json_decode($response, true);
        echo json_encode($result);
    } catch (Exception $e) {
        error_log('Cast image upload error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}