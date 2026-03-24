<?php
/**
 * Admin API
 * GET /api/admin.php?action=subscribers&password=admin123
 * GET /api/admin.php?action=contacts&password=admin123
 * DELETE /api/admin.php?action=delete_subscriber&id=1&password=admin123
 * DELETE /api/admin.php?action=delete_contact&id=1&password=admin123
 * GET /api/admin.php?action=stats&password=admin123
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ──── Admin Password ────
// Change this to your own secure password!
define('ADMIN_PASSWORD', 'admin123');

// Authentication check
$password = $_GET['password'] ?? '';
if ($password !== ADMIN_PASSWORD) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Invalid password.']);
    exit;
}

// Get action
$action = $_GET['action'] ?? '';

// Connect to database
require_once __DIR__ . '/database.php';

try {
    switch ($action) {

        case 'stats':
            $subCount = $db->query('SELECT COUNT(*) as count FROM subscribers')->fetch()['count'];
            $conCount = $db->query('SELECT COUNT(*) as count FROM contacts')->fetch()['count'];
            echo json_encode([
                'success' => true,
                'subscribers_count' => $subCount,
                'contacts_count' => $conCount
            ]);
            break;

        case 'subscribers':
            $stmt = $db->query('SELECT * FROM subscribers ORDER BY subscribed_at DESC');
            $subscribers = $stmt->fetchAll();
            echo json_encode([
                'success' => true,
                'data' => $subscribers,
                'count' => count($subscribers)
            ]);
            break;

        case 'contacts':
            $stmt = $db->query('SELECT * FROM contacts ORDER BY created_at DESC');
            $contacts = $stmt->fetchAll();
            echo json_encode([
                'success' => true,
                'data' => $contacts,
                'count' => count($contacts)
            ]);
            break;

        case 'delete_subscriber':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }
            $stmt = $db->prepare('DELETE FROM subscribers WHERE id = :id');
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Subscriber deleted.']);
            break;

        case 'delete_contact':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }
            $stmt = $db->prepare('DELETE FROM contacts WHERE id = :id');
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Contact message deleted.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action. Use: stats, subscribers, contacts, delete_subscriber, delete_contact']);
            break;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
