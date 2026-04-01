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
$csrf_token = $data['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['error' => 'CSRF failure']);
    exit;
}

if (!$post_id) {
    echo json_encode(['error' => 'Invalid post ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if already liked
$stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$like = $stmt->fetch();

if ($like) {
    // Unlike
    $pdo->prepare("DELETE FROM likes WHERE id = ?")->execute([$like['id']]);
    $liked = false;
} else {
    // Like
    $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
    $liked = true;
    
    // Reward author reputation point (optional extra feature but nice)
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post_author = $stmt->fetchColumn();
    if ($post_author && $post_author != $user_id) { // don't reward own likes
        $pdo->prepare("UPDATE users SET reputation = reputation + 1 WHERE id = ?")->execute([$post_author]);
    }
}

// Get new count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$stmt->execute([$post_id]);
$count = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'count' => $count
]);
