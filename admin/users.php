<?php
require_once '../config.php';
require_once '../helpers.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $target_user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    // Check target role
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $target_role = $stmt->fetchColumn();
    
    // Don't modify super admins unless you are an admin (and arguably not even then, but simple rules here)
    if ($target_role !== 'admin' || $_SESSION['role'] === 'admin') {
        if ($action === 'ban') {
            $pdo->prepare("UPDATE users SET role = 'banned' WHERE id = ?")->execute([$target_user_id]);
            $_SESSION['flash_success'] = "User has been banned.";
        } elseif ($action === 'unban') {
            $pdo->prepare("UPDATE users SET role = 'member' WHERE id = ?")->execute([$target_user_id]);
            $_SESSION['flash_success'] = "User ban lifted.";
        } elseif ($action === 'promote' && $_SESSION['role'] === 'admin') {
            $pdo->prepare("UPDATE users SET role = 'moderator' WHERE id = ?")->execute([$target_user_id]);
            $_SESSION['flash_success'] = "User promoted to moderator.";
        } elseif ($action === 'demote' && $_SESSION['role'] === 'admin') {
            $pdo->prepare("UPDATE users SET role = 'member' WHERE id = ?")->execute([$target_user_id]);
            $_SESSION['flash_success'] = "User demoted to member.";
        }
    } else {
        $_SESSION['flash_error'] = "You do not have permission to modify this user.";
    }
    header('Location: users.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel - ForumHub</title>
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
                <a href="reports.php"><i class="fas fa-flag"></i> Reports</a>
                <a href="users.php" class="active"><i class="fas fa-users"></i> Users</a>
                <a href="categories.php"><i class="fas fa-folder"></i> Categories</a>
            </nav>

            <h2 style="margin-bottom: 1.5rem;">Manage Users</h2>
            
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="flash-message flash-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="flash-message flash-error"><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
            <?php endif; ?>

            <div class="card" style="padding: 0; overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <?php if ($user['avatar_url']): ?>
                                            <img src="../<?= htmlspecialchars($user['avatar_url']) ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="avatar-initials" style="width: 30px; height: 30px; font-size: 1rem;">
                                                <?= get_avatar_initials($user['username']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <a href="../profile.php?id=<?= $user['id'] ?>" target="_blank" style="color:var(--text); text-decoration:none; font-weight: 500;">
                                            <?= htmlspecialchars($user['username']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php
                                        $rc = 'bg'; $tc = 'text';
                                        if($user['role'] == 'admin') { $rc = 'danger'; $tc = 'white'; }
                                        elseif($user['role'] == 'moderator') { $rc = 'primary'; $tc = 'white'; }
                                        elseif($user['role'] == 'banned') { $rc = 'muted'; $tc = 'white'; }
                                    ?>
                                    <span class="badge badge-<?= $rc ?>" style="color: <?= $tc == 'white' ? 'white' : 'var(--text)' ?>;"><?= ucfirst($user['role']) ?></span>
                                    <div style="font-size: 0.75rem; color: var(--muted); margin-top:2px;">Rep: <?= $user['reputation'] ?></div>
                                </td>
                                <td style="font-size: 0.875rem;"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <?php if ($user['role'] !== 'banned'): ?>
                                            <form method="POST" action="" class="require-confirm" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="action" value="ban">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-ban"></i> Ban</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="action" value="unban">
                                                <button type="submit" class="btn btn-secondary btn-sm" style="background:var(--success); color:white;"><i class="fas fa-check"></i> Unban</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <?php if ($user['role'] === 'member'): ?>
                                                <form method="POST" action="" class="require-confirm" style="display:inline; margin-left: 0.5rem;">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <input type="hidden" name="action" value="promote">
                                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-arrow-up"></i> Make Mod</button>
                                                </form>
                                            <?php elseif ($user['role'] === 'moderator'): ?>
                                                <form method="POST" action="" class="require-confirm" style="display:inline; margin-left: 0.5rem;">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <input type="hidden" name="action" value="demote">
                                                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-down"></i> Demote</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--muted); font-size: 0.875rem;">It's you!</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../scripts.js"></script>
</body>
</html>
