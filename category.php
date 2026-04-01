<?php
require_once 'config.php';
require_once 'helpers.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    die("Category not found.");
}

// Pagination
$per_page = 10;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE category_id = ? AND is_deleted = 0");
$stmt->execute([$category_id]);
$total_topics = $stmt->fetchColumn();

// Fetch topics
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.avatar_url,
    (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) - 1 as reply_count
    FROM topics t
    JOIN users u ON t.user_id = u.id
    WHERE t.category_id = ? AND t.is_deleted = 0
    ORDER BY t.is_pinned DESC, t.updated_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute([$category_id]);
$topics = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['name']) ?> - ForumHub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="card">
                <h3 style="margin-bottom:0.5rem;"><i class="<?= htmlspecialchars($category['icon']) ?>"></i> <?= htmlspecialchars($category['name']) ?></h3>
                <p style="color:var(--muted); font-size: 0.875rem; margin-bottom: 1rem;"><?= htmlspecialchars($category['description']) ?></p>
                <div style="font-weight: bold;"><?= $category['post_count'] ?> Topics</div>
            </div>
            
            <div class="card">
                 <a href="index.php" class="btn btn-secondary btn-sm" style="width: 100%; text-align: center;"><i class="fas fa-arrow-left"></i> All Categories</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2><?= htmlspecialchars($category['name']) ?> Topics</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="new_topic.php?category_id=<?= $category['id'] ?>" class="btn btn-primary"><i class="fas fa-plus"></i> New Topic</a>
                <?php endif; ?>
            </div>

            <?php if (empty($topics)): ?>
                <div class="card"><p>No topics in this category yet. Be the first to start a conversation!</p></div>
            <?php else: ?>
                <?php foreach ($topics as $topic): ?>
                    <a href="topic.php?id=<?= $topic['id'] ?>" class="card topic-card">
                        <div>
                            <div class="topic-title">
                                <?php if ($topic['is_pinned']): ?>
                                    <i class="fas fa-thumbtack pinned"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($topic['title']) ?>
                                <?php if ($topic['reply_count'] > 20): ?>
                                    <span class="badge badge-danger">Hot</span>
                                <?php endif; ?>
                                <?php if ($topic['is_locked']): ?>
                                    <i class="fas fa-lock" style="color:var(--muted); font-size: 0.8em;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="topic-meta">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($topic['username']) ?></span>
                                <span><i class="fas fa-clock"></i> <?= time_ago($topic['created_at']) ?></span>
                            </div>
                        </div>
                        <div style="text-align: right; color: var(--muted); font-size: 0.875rem; white-space: nowrap;">
                            <div><i class="fas fa-comment"></i> <?= $topic['reply_count'] ?> replies</div>
                            <div><i class="fas fa-eye"></i> <?= $topic['views'] ?> views</div>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <?= paginate($total_topics, $per_page, $current_page, "&id=" . $category['id']) ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
