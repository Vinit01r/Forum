<?php
require_once 'config.php';
header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$search = "%{$q}%";

$stmt = $pdo->prepare("
    SELECT t.id, t.title, c.name as category_name
    FROM topics t
    JOIN categories c ON t.category_id = c.id
    WHERE t.is_deleted = 0 AND t.title LIKE ?
    ORDER BY t.views DESC
    LIMIT 10
");
$stmt->execute([$search]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
