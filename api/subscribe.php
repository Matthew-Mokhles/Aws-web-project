<?php
/**
 * Newsletter Subscription API
 * POST /api/subscribe.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input data (supports both JSON and form-data)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');

// Validation
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name is required.']);
    exit;
}

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Connect to database
require_once __DIR__ . '/database.php';

try {
    // Check for duplicate email
    $stmt = $db->prepare('SELECT id FROM subscribers WHERE email = :email');
    $stmt->execute([':email' => strtolower($email)]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed to our newsletter!']);
        exit;
    }

    // Insert new subscriber
    $stmt = $db->prepare('INSERT INTO subscribers (name, email) VALUES (:name, :email)');
    $stmt->execute([
        ':name' => $name,
        ':email' => strtolower($email)
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Welcome to the Aswan Newsletter! 🎉',
        'id' => $db->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}
