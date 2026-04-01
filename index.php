<?php
require_once 'config.php';
require_once 'helpers.php';

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Fetch recent topics across all categories
$stmt = $pdo->query("
    SELECT t.*, c.name as category_name, c.icon as category_icon, u.username, u.avatar_url,
    (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) - 1 as reply_count
    FROM topics t
    JOIN categories c ON t.category_id = c.id
    JOIN users u ON t.user_id = u.id
    WHERE t.is_deleted = 0
    ORDER BY t.created_at DESC LIMIT 10
");
$recent_topics = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - ForumHub</title>
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
                <h3><i class="fas fa-list"></i> Categories</h3>
                <ul class="category-list">
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="category.php?id=<?= $cat['id'] ?>">
                                <i class="<?= htmlspecialchars($cat['icon']) ?>" style="width: 20px; text-align: center;"></i>
                                <?= htmlspecialchars($cat['name']) ?>
                                <span class="badge badge-secondary" style="float:right;"><?= $cat['post_count'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="card" style="text-align: center;">
                <?php
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $me = $stmt->fetch();
                ?>
                <?php if ($me['avatar_url']): ?>
                    <img src="<?= htmlspecialchars($me['avatar_url']) ?>" alt="Avatar" class="avatar" style="width:80px;height:80px; margin-bottom: 15px;">
                <?php else: ?>
                    <div class="avatar-initials" style="width:80px;height:80px; font-size: 2rem; margin: 0 auto 15px auto;">
                        <?= get_avatar_initials($me['username']) ?>
                    </div>
                <?php endif; ?>
                <h4><?= htmlspecialchars($me['username']) ?></h4>
                <div style="margin-top: 10px;">
                    <?= get_reputation_badge($me['reputation']) ?>
                </div>
                <div style="margin-top: 15px;">
                    <a href="profile.php?id=<?= $me['id'] ?>" class="btn btn-secondary btn-sm" style="width: 100%;">My Profile</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="flash-message flash-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="flash-message flash-error"><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>Recent Discussions</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="new_topic.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Discussion</a>
                <?php endif; ?>
            </div>

            <?php if (empty($recent_topics)): ?>
                <div class="card"><p>No topics found. Be the first to start a conversation!</p></div>
            <?php else: ?>
                <?php foreach ($recent_topics as $topic): ?>
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
                                <span class="badge badge-primary">
                                    <i class="<?= htmlspecialchars($topic['category_icon']) ?>"></i> <?= htmlspecialchars($topic['category_name']) ?>
                                </span>
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
            <?php endif; ?>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
