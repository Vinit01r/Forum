<?php
require_once '../config.php';
require_once '../helpers.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['action'];
    
    $stmt = $pdo->prepare("SELECT post_id, reported_by FROM reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if ($report) {
         if ($action === 'dismiss') {
             $pdo->prepare("UPDATE reports SET status = 'dismissed' WHERE id = ?")->execute([$report_id]);
         } elseif ($action === 'delete') {
             $pdo->prepare("UPDATE posts SET is_deleted = 1 WHERE id = ?")->execute([$report['post_id']]);
             $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE post_id = ?")->execute([$report['post_id']]);
             
             // Decrement topic post count implicitly by filtering views, but maybe clean up topic if it's the first post.
             // We'll keep it simple: soft delete the post.
         } elseif ($action === 'warn') {
             $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
             $stmt->execute([$report['post_id']]);
             $offender_id = $stmt->fetchColumn();
             if ($offender_id) {
                 $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'warning', 'Your post was reported and you are receiving an official warning.', '#')")->execute([$offender_id]);
             }
             $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$report_id]);
         }
         $_SESSION['flash_success'] = "Report handled successfully.";
    }
    header('Location: reports.php');
    exit;
}

$stmt = $pdo->query("
    SELECT r.*, p.content as post_content, p.topic_id, u1.username as reporter, u2.username as offender 
    FROM reports r 
    JOIN posts p ON r.post_id = p.id 
    JOIN users u1 ON r.reported_by = u1.id 
    JOIN users u2 ON p.user_id = u2.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at ASC
");
$pending_reports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel - ForumHub</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
    <style>
        .admin-nav {
            background-color: var(--card);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            border: 1px solid var(--border);
        }
        .admin-nav a {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: var(--text);
            border-radius: 4px;
            font-weight: 500;
        }
        .admin-nav a.active, .admin-nav a:hover {
            background-color: var(--primary);
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        th { backgroundColor: var(--bg); }
    </style>
</head>
<body>
    <nav class="navbar" style="background: #1e1e2d; border-bottom: none;">
        <a href="../index.php" class="navbar-brand" style="color: white;">
            <i class="fas fa-shield-alt"></i> ForumHub <span style="font-size: 0.7em; font-weight: normal; opacity: 0.8;">Admin Panel</span>
        </a>
        <div class="nav-links">
            <a href="../index.php" style="color: white;"><i class="fas fa-arrow-left"></i> Back to Forum</a>
        </div>
    </nav>
    
    <div class="container">
        <div style="width: 100%;">
            <nav class="admin-nav">
                <a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="reports.php" class="active"><i class="fas fa-flag"></i> Reports</a>
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
                <a href="categories.php"><i class="fas fa-folder"></i> Categories</a>
            </nav>

            <h2 style="margin-bottom: 1.5rem;">Pending Reports</h2>
            
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="flash-message flash-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
            <?php endif; ?>

            <div class="card" style="padding: 0; overflow-x: auto;">
                <?php if (empty($pending_reports)): ?>
                    <p style="padding: 2rem; color: var(--muted); text-align: center;">No pending reports! Great job.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Report Details</th>
                                <th>Post Content</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_reports as $report): ?>
                                <tr>
                                    <td style="vertical-align: top; width: 25%;">
                                        <strong>Reported by:</strong> <?= htmlspecialchars($report['reporter']) ?><br>
                                        <strong>Date:</strong> <?= date('M j, Y H:i', strtotime($report['created_at'])) ?><br>
                                        <div style="margin-top:0.5rem; background: var(--bg); padding:0.5rem; border-radius:4px;">
                                            <strong style="color: var(--danger);">Reason:</strong><br>
                                            <?= nl2br(htmlspecialchars($report['reason'])) ?>
                                        </div>
                                    </td>
                                    <td style="vertical-align: top; width: 50%;">
                                        <div style="margin-bottom: 0.5rem; font-size: 0.875rem;">
                                            <strong>Author:</strong> <?= htmlspecialchars($report['offender']) ?> | 
                                            <a href="../topic.php?id=<?= $report['topic_id'] ?>#post-<?= $report['post_id'] ?>" target="_blank">View in Topic <i class="fas fa-external-link-alt"></i></a>
                                        </div>
                                        <div style="background: #fafafa; padding: 1rem; border: 1px solid var(--border); border-radius: 4px;">
                                            <?php 
                                            $content = $report['post_content'];
                                            echo nl2br(htmlspecialchars(mb_substr($content, 0, 300) . (mb_strlen($content) > 300 ? '...' : '')));
                                            ?>
                                        </div>
                                    </td>
                                    <td style="vertical-align: top; width: 25%;">
                                        <form method="POST" action="" class="require-confirm" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" style="width: 100%; margin-bottom: 0.5rem;"><i class="fas fa-trash"></i> Delete Post</button>
                                        </form>
                                        
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                            <input type="hidden" name="action" value="warn">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; margin-bottom: 0.5rem; background: #f29900; color: white;"><i class="fas fa-exclamation-triangle"></i> Warn User</button>
                                        </form>
                                        
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                            <input type="hidden" name="action" value="dismiss">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;"><i class="fas fa-times"></i> Dismiss Report</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../scripts.js"></script>
</body>
</html>
