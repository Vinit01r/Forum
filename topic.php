<?php
require_once 'config.php';
require_once 'helpers.php';

$topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, c.icon as category_icon, u.username as author_name 
    FROM topics t 
    JOIN categories c ON t.category_id = c.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.is_deleted = 0
");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if (!$topic) die("Topic not found or deleted.");

// Update views only once per session per topic ideally, but simplified here
if (!isset($_SESSION['viewed_topic_' . $topic_id])) {
    $pdo->prepare("UPDATE topics SET views = views + 1 WHERE id = ?")->execute([$topic_id]);
    $_SESSION['viewed_topic_' . $topic_id] = true;
}

// Reply handling
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content']) && isset($_SESSION['user_id'])) {
    verify_csrf();
    if ($topic['is_locked']) {
        $error = "This topic is locked. You cannot reply.";
    } else {
        $content = sanitize_input($_POST['reply_content']);
        if (empty($content)) {
            $error = "Reply content cannot be empty.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO posts (topic_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$topic_id, $_SESSION['user_id'], $content]);
            
            // Update topic updated_at
            $pdo->prepare("UPDATE topics SET updated_at = NOW() WHERE id = ?")->execute([$topic_id]);
            
            // Give reputation point to user for engaging
            $pdo->prepare("UPDATE users SET reputation = reputation + 1 WHERE id = ?")->execute([$_SESSION['user_id']]);
            
            $_SESSION['flash_success'] = "Reply posted!";
            header("Location: topic.php?id=$topic_id&page=9999");
            exit;
        }
    }
}

// Pagination for posts
$per_page = 15;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
if ($current_page > 1000) {
    // Handling jumping to the last page dynamically
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE topic_id = ? AND is_deleted = 0");
    $stmt->execute([$topic_id]);
    $total = $stmt->fetchColumn();
    $current_page = max(1, ceil($total / $per_page));
}
$offset = ($current_page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE topic_id = ? AND is_deleted = 0");
$stmt->execute([$topic_id]);
$total_posts = $stmt->fetchColumn();

// Fetch posts
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar_url, u.reputation, u.created_at as join_date, u.role,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.topic_id = ? AND p.is_deleted = 0
    ORDER BY p.created_at ASC
    LIMIT ?, ?
");
$user_id = $_SESSION['user_id'] ?? 0;
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $topic_id, PDO::PARAM_INT);
$stmt->bindParam(3, $offset, PDO::PARAM_INT);
$stmt->bindParam(4, $per_page, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($topic['title']) ?> - ForumHub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
    <!-- pass user info to JS for CSRF -->
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container" style="flex-direction: column;">
        <div style="margin-bottom: 1rem;">
            <a href="category.php?id=<?= $topic['category_id'] ?>" style="color: var(--muted); text-decoration: none;">
                <i class="<?= htmlspecialchars($topic['category_icon']) ?>"></i> <?= htmlspecialchars($topic['category_name']) ?>
            </a>
            <span style="color:var(--muted); margin: 0 0.5rem;">/</span>
            <span style="color:var(--text);"><?= htmlspecialchars($topic['title']) ?></span>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2>
                <?php if ($topic['is_pinned']): ?><i class="fas fa-thumbtack pinned"></i><?php endif; ?>
                <?php if ($topic['is_locked']): ?><i class="fas fa-lock" style="color:var(--muted);"></i><?php endif; ?>
                <?= htmlspecialchars($topic['title']) ?>
            </h2>
        </div>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="flash-message flash-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <div class="post-card" id="post-<?= $post['id'] ?>">
                <div class="post-author">
                    <?php if ($post['avatar_url']): ?>
                        <img src="<?= htmlspecialchars($post['avatar_url']) ?>" alt="Avatar" class="avatar" style="width: 80px; height: 80px; margin-bottom: 10px;">
                    <?php else: ?>
                        <div class="avatar-initials" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 10px auto;">
                            <?= get_avatar_initials($post['username']) ?>
                        </div>
                    <?php endif; ?>
                    <div style="font-weight: bold; margin-bottom: 5px;">
                        <a href="profile.php?id=<?= $post['user_id'] ?>" style="color:var(--text); text-decoration:none;"><?= htmlspecialchars($post['username']) ?></a>
                    </div>
                    <?php if ($post['role'] === 'admin'): ?>
                        <div class="badge badge-danger" style="margin-bottom:5px;">Admin</div>
                    <?php elseif ($post['role'] === 'moderator'): ?>
                        <div class="badge badge-primary" style="margin-bottom:5px;">Moderator</div>
                    <?php endif; ?>
                    <div style="margin-bottom: 5px;"><?= get_reputation_badge($post['reputation']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--muted);">Joined: <?= date('M Y', strtotime($post['join_date'])) ?></div>
                </div>
                
                <div class="post-content">
                    <div style="font-size: 0.875rem; color: var(--muted); margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; display: flex; justify-content: space-between;">
                        <span><i class="fas fa-calendar-alt"></i> <?= date('M j, Y, g:i a', strtotime($post['created_at'])) ?> (<?= time_ago($post['created_at']) ?>)</span>
                        <a href="#post-<?= $post['id'] ?>" style="color:var(--muted); text-decoration:none;">#<?= $post['id'] ?></a>
                    </div>
                    
                    <div class="post-body"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
                    
                    <div class="post-actions">
                        <button class="btn btn-secondary btn-sm like-btn" data-id="<?= $post['id'] ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?> style="display:flex; align-items:center; gap:0.5rem; border:1px solid <?= $post['user_liked'] ? 'var(--danger)' : 'transparent' ?>; color: <?= $post['user_liked'] ? 'var(--danger)' : 'white' ?>; background: <?= $post['user_liked'] ? 'transparent' : 'var(--muted)' ?>;">
                            <i class="fas fa-heart"></i>
                            <span class="like-count"><?= $post['like_count'] ?></span>
                        </button>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-secondary btn-sm quote-btn" data-username="<?= htmlspecialchars($post['username']) ?>" data-content="<?= htmlspecialchars(strip_tags($post['content'])) ?>">
                                <i class="fas fa-quote-right"></i> Quote
                            </button>
                            <!-- Do not allow users to report their own posts -->
                            <?php if ($_SESSION['user_id'] != $post['user_id']): ?>
                                <button class="btn btn-secondary btn-sm report-btn" data-post-id="<?= $post['id'] ?>" style="margin-left:auto;">
                                    <i class="fas fa-flag"></i> Report
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?= paginate($total_posts, $per_page, $current_page, "&id=" . $topic_id) ?>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="card" style="text-align: center; margin-top: 2rem;">
                <p>You must be logged in to reply to this topic.</p>
                <div style="margin-top: 1rem;">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
            </div>
        <?php elseif ($topic['is_locked']): ?>
            <div class="card" style="text-align: center; margin-top: 2rem; background-color: var(--bg);">
                <p><i class="fas fa-lock"></i> This topic is locked. You cannot reply.</p>
            </div>
        <?php else: ?>
            <div class="card" style="margin-top: 2rem;" id="replySection">
                <h3>Post a Reply</h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <textarea name="reply_content" id="replyContent" class="form-control" required placeholder="Write your reply..."></textarea>
                        <div style="text-align: right; font-size: 0.8em; color: var(--muted);" id="charCount">0 characters</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Reply</button>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <!-- Report Modal -->
    <div class="modal" id="reportModal">
        <div class="modal-content">
            <h3>Report Post</h3>
            <p style="font-size: 0.875rem; color: var(--muted); margin-bottom: 1rem;">Help us keep the community clean and safe.</p>
            <input type="hidden" id="reportPostId">
            <div class="form-group">
                <label>Reason for reporting:</label>
                <select id="reportReasonSelect" class="form-control" style="margin-bottom: 0.5rem;">
                    <option value="Spam">Spam or Advertisement</option>
                    <option value="Offensive">Offensive or Abusive</option>
                    <option value="Off-topic">Off-topic</option>
                    <option value="Other">Other</option>
                </select>
                <textarea id="reportDetails" class="form-control" rows="3" placeholder="Provide any additional details..."></textarea>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                <button class="btn btn-secondary" id="closeReportModal">Cancel</button>
                <button class="btn btn-danger" id="submitReport">Submit Report</button>
            </div>
        </div>
    </div>

    <script src="scripts.js"></script>
</body>
</html>
