<?php
require_once 'config.php';
require_once 'helpers.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

if (!$u) die("User not found.");

// Fetch recent topics by user
$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name
    FROM topics t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.is_deleted = 0
    ORDER BY t.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_topics = $stmt->fetchAll();

// Fetch recent posts by user
$stmt = $pdo->prepare("
    SELECT p.*, t.title as topic_title
    FROM posts p
    JOIN topics t ON p.topic_id = t.id
    WHERE p.user_id = ? AND p.is_deleted = 0
    ORDER BY p.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($u['username']) ?> - Profile - ForumHub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <!-- Sidebar Profile Card -->
        <div class="sidebar">
            <div class="card" style="text-align: center;">
                <?php if ($u['avatar_url']): ?>
                    <img src="<?= htmlspecialchars($u['avatar_url']) ?>" alt="Avatar" class="avatar" style="width: 100px; height: 100px; margin-bottom: 1rem;">
                <?php else: ?>
                    <div class="avatar-initials" style="width: 100px; height: 100px; font-size: 2.5rem; margin: 0 auto 1rem auto;">
                        <?= get_avatar_initials($u['username']) ?>
                    </div>
                <?php endif; ?>
                
                <h3 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($u['username']) ?></h3>
                
                <?php if ($u['role'] === 'admin'): ?>
                    <div class="badge badge-danger" style="margin-bottom:1rem; display:inline-block;">Admin</div>
                <?php elseif ($u['role'] === 'moderator'): ?>
                    <div class="badge badge-primary" style="margin-bottom:1rem; display:inline-block;">Moderator</div>
                <?php endif; ?>
                
                <div style="margin-bottom: 1rem;">
                    <?= get_reputation_badge($u['reputation']) ?>
                </div>
                
                <div style="font-size: 0.875rem; color: var(--muted); margin-bottom: 0.5rem;">
                    <i class="fas fa-calendar-alt"></i> Joined <?= date('M Y', strtotime($u['created_at'])) ?>
                </div>
                
                <div style="font-size: 0.875rem; color: var(--muted); margin-bottom: 1.5rem;">
                    <i class="fas fa-star"></i> <?= $u['reputation'] ?> Reputation
                </div>
                
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $u['id']): ?>
                    <a href="edit_profile.php" class="btn btn-primary btn-sm" style="width: 100%;"><i class="fas fa-edit"></i> Edit Profile</a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($u['bio'])): ?>
            <div class="card">
                <h4 style="margin-bottom: 0.5rem;">About</h4>
                <p style="font-size: 0.875rem; color: var(--muted);"><?= nl2br(htmlspecialchars($u['bio'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content (Activity) -->
        <div class="main-content">
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;"><i class="fas fa-comments"></i> Recent Topics</h3>
                <?php if (empty($recent_topics)): ?>
                    <p style="color: var(--muted);">This user hasn't created any topics yet.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($recent_topics as $topic): ?>
                            <li style="border-bottom: 1px solid var(--border); padding: 1rem 0;">
                                <div style="font-weight: 500;">
                                    <a href="topic.php?id=<?= $topic['id'] ?>" style="color: var(--text); text-decoration: none;"><?= htmlspecialchars($topic['title']) ?></a>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.5rem;">
                                    <span class="badge badge-secondary" style="font-size: 0.65rem;"><?= htmlspecialchars($topic['category_name']) ?></span>
                                    <span style="margin-left: 0.5rem;"><i class="fas fa-clock"></i> <?= time_ago($topic['created_at']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 style="margin-bottom: 1rem;"><i class="fas fa-reply"></i> Recent Replies</h3>
                <?php if (empty($recent_posts)): ?>
                    <p style="color: var(--muted);">This user hasn't replied to any topics yet.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($recent_posts as $post): ?>
                            <li style="border-bottom: 1px solid var(--border); padding: 1rem 0;">
                                <div style="font-size: 0.875rem; color: var(--muted); margin-bottom: 0.5rem;">
                                    Replied to <a href="topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post['id'] ?>" style="color: var(--primary); text-decoration: none; font-weight: 500;"><?= htmlspecialchars($post['topic_title']) ?></a>
                                    • <?= time_ago($post['created_at']) ?>
                                </div>
                                <div style="background: var(--bg); padding: 1rem; border-radius: 4px; font-size: 0.875rem;">
                                    <?php 
                                        $trimmed = mb_substr($post['content'], 0, 150);
                                        echo htmlspecialchars($trimmed) . (mb_strlen($post['content']) > 150 ? '...' : ''); 
                                    ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="scripts.js"></script>
</body>
</html>
