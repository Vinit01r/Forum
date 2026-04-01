<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON post data
$data = json_decode(file_get_contents('php://input'), true);
$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
$reason = $data['reason'] ?? '';
$csrf_token = $data['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['error' => 'CSRF failure']);
    exit;
}

if (!$post_id || empty($reason)) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("INSERT INTO reports (post_id, reported_by, reason) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, sanitize_input($reason)]);
    
    // Also increment report count on the post
    $pdo->prepare("UPDATE posts SET reported_count = reported_count + 1 WHERE id = ?")->execute([$post_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
