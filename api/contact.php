<?php
/**
 * Contact Form API
 * POST /api/contact.php
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

// Get input data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$firstName = trim($input['first_name'] ?? '');
$lastName = trim($input['last_name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

// Validation
if (empty($firstName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'First name is required.']);
    exit;
}

if (empty($lastName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Last name is required.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
    exit;
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is required.']);
    exit;
}

// Connect to database
require_once __DIR__ . '/database.php';

try {
    $stmt = $db->prepare('
        INSERT INTO contacts (first_name, last_name, email, phone, subject, message)
        VALUES (:first_name, :last_name, :email, :phone, :subject, :message)
    ');

    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => strtolower($email),
        ':phone' => $phone,
        ':subject' => $subject,
        ':message' => $message
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for contacting us! We will get back to you soon. 📩',
        'id' => $db->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}
