<?php
require_once '../config.php';
require_once '../helpers.php';

// Only full admins for category management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'moderator') {
        $_SESSION['flash_error'] = "Only administrators can manage categories.";
        header('Location: index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'];
    
    if ($action === 'create') {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $icon = sanitize_input($_POST['icon']);
        $slug = generate_slug($name);
        
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
            try {
                $stmt->execute([$name, $slug, $description, $icon]);
                $_SESSION['flash_success'] = "Category created successfully.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error creating category. Name might already exist.";
            }
        }
    } elseif ($action === 'delete') {
        $cat_id = (int)$_POST['category_id'];
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cat_id]);
        $_SESSION['flash_success'] = "Category deleted.";
    }
    
    header('Location: categories.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel - ForumHub</title>
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
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
                <a href="categories.php" class="active"><i class="fas fa-folder"></i> Categories</a>
            </nav>

            <h2 style="margin-bottom: 1.5rem;">Manage Categories</h2>
            
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="flash-message flash-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="flash-message flash-error"><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
            <?php endif; ?>

            <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                
                <!-- Create Form -->
                <div class="card" style="flex: 1; min-width: 300px; align-self: flex-start;">
                    <h3>Create New Category</h3>
                    <form method="POST" action="" style="margin-top: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Alumni Network">
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief description visible to users"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>FontAwesome Icon Class</label>
                            <input type="text" name="icon" class="form-control" placeholder="e.g. fas fa-graduation-cap">
                            <div style="font-size: 0.8em; color: var(--muted); margin-top: 0.25rem;">Find icons at fontawesome.com</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-plus"></i> Create Category</button>
                    </form>
                </div>

                <!-- List Current Categories -->
                <div class="card" style="flex: 2; min-width: 300px; padding: 0;">
                    <h3 style="padding: 1.5rem; border-bottom: 1px solid var(--border);">Current Categories</h3>
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Name / Icon</th>
                                <th>Topics</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <i class="<?= htmlspecialchars($cat['icon']) ?>" style="color:var(--primary); font-size:1.2rem; width:25px; text-align:center;"></i>
                                            <div>
                                                <div style="font-weight: 500;"><?= htmlspecialchars($cat['name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--muted);"><?= htmlspecialchars($cat['slug']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $cat['post_count'] ?></td>
                                    <td>
                                        <form method="POST" action="" class="require-confirm">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" <?= $cat['post_count'] > 0 ? 'title="Cannot delete non-empty categories disabled' : '' ?> <?= $cat['post_count'] > 0 ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    <script src="../scripts.js"></script>
</body>
</html>
