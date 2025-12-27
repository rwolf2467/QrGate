<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check if admin session exists
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

    // Calculate statistics
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

    // Ensure store_lock is treated as boolean
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

function addDay() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['date']) || !isset($input['time']) || !isset($input['tickets']) || !isset($input['price'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        return;
    }

    // Load current shows
    $shows = getShows();
    if (!$shows) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not load shows']);
        return;
    }

    // Use the provided dateId if available, otherwise generate a new one
    $newDateId = $input['dateId'] ?? null;
    if (empty($newDateId)) {
        // Generate a new date ID - use timestamp-based ID for uniqueness
        $existingIds = array_keys($shows['dates'] ?? []);
        if (empty($existingIds)) {
            $newDateId = 'day_' . time();
        } else {
            // Find the highest timestamp and add 1
            $timestamps = [];
            foreach ($existingIds as $id) {
                if (strpos($id, 'day_') === 0) {
                    $timestamps[] = (int)substr($id, 4);
                }
            }
            $newDateId = 'day_' . (max($timestamps) + 1);
        }
    }
    
    // Check if the dateId already exists
    if (isset($shows['dates'][$newDateId])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Date ID already exists']);
        return;
    }
    
    // Add the new day
    $shows['dates'][$newDateId] = [
        'date' => $input['date'],
        'time' => $input['time'],
        'tickets' => (int)$input['tickets'],
        'tickets_available' => (int)$input['tickets'],
        'price' => (string)$input['price']
    ];

    // Update the show
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
    
    // Log the input for debugging
    error_log('updateDay input: ' . print_r($input, true));

    // Load current shows
    $shows = getShows();
    if (!$shows) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not load shows']);
        return;
    }

    // Update the specific day
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

    // Update the show
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
    
    // Log the input for debugging
    error_log('deleteDay input: ' . print_r($input, true));
    
    if (!$input || !isset($input['dateId'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing dateId']);
        return;
    }

    // Load current shows
    $shows = getShows();
    if (!$shows) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not load shows']);
        return;
    }

    // Remove the specific day
    if (!isset($shows['dates'][$input['dateId']])) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Day not found']);
        return;
    }

    unset($shows['dates'][$input['dateId']]);

    // Update the show
    $result = updateShow($shows);
    
    // Log the result for debugging
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