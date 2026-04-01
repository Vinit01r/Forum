<?php
require_once '../config.php';
require_once '../helpers.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: ../login.php');
    exit;
}

$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'topics' => $pdo->query("SELECT COUNT(*) FROM topics WHERE is_deleted = 0")->fetchColumn(),
    'posts' => $pdo->query("SELECT COUNT(*) FROM posts WHERE is_deleted = 0")->fetchColumn(),
    'reports' => $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ForumHub</title>
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
                <a href="index.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="reports.php"><i class="fas fa-flag"></i> Reports <?php if($stats['reports'] > 0) echo '<span class="badge badge-danger">'.$stats['reports'].'</span>'; ?></a>
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
                <a href="categories.php"><i class="fas fa-folder"></i> Categories</a>
            </nav>

            <h2 style="margin-bottom: 1.5rem;">Dashboard Overview</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card" style="text-align: center; background: #e8f0fe; border: 1px solid #c2e0ff;">
                    <i class="fas fa-users" style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
                    <h3><?= $stats['users'] ?></h3>
                    <div style="color: var(--muted); font-size: 0.875rem;">Total Users</div>
                </div>
                <div class="card" style="text-align: center; background: #e6f4ea; border: 1px solid #ceead6;">
                    <i class="fas fa-comments" style="font-size: 2rem; color: var(--success); margin-bottom: 0.5rem;"></i>
                    <h3><?= $stats['topics'] ?></h3>
                    <div style="color: var(--muted); font-size: 0.875rem;">Total Topics</div>
                </div>
                <div class="card" style="text-align: center; background: #fef7e0; border: 1px solid #feefc3;">
                    <i class="fas fa-reply" style="font-size: 2rem; color: #f29900; margin-bottom: 0.5rem;"></i>
                    <h3><?= $stats['posts'] ?></h3>
                    <div style="color: var(--muted); font-size: 0.875rem;">Total Posts</div>
                </div>
                <div class="card" style="text-align: center; background: #fce8e6; border: 1px solid #fad2cf;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: var(--danger); margin-bottom: 0.5rem;"></i>
                    <h3><?= $stats['reports'] ?></h3>
                    <div style="color: var(--muted); font-size: 0.875rem;">Pending Reports</div>
                </div>
            </div>
            
            <div class="card">
                <h3>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h3>
                <p style="color: var(--muted); margin-top: 0.5rem;">Use the navigation tools above to manage the community.</p>
            </div>
        </div>
    </div>
</body>
</html>
