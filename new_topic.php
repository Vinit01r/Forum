<?php
require_once 'config.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $category_id = (int)$_POST['category_id'];
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($category_id) || empty($title) || empty($content)) {
        $error = "All fields are required.";
    } else {
        $slug = generate_slug($title) . '-' . time();
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO topics (category_id, user_id, title, slug, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$category_id, $user_id, $title, $slug]);
            $topic_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO posts (topic_id, user_id, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$topic_id, $user_id, $content]);
            
            $pdo->query("UPDATE categories SET post_count = post_count + 1 WHERE id = $category_id");
            
            $pdo->commit();
            
            $_SESSION['flash_success'] = "Topic created successfully!";
            header("Location: topic.php?id=$topic_id");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to create topic: " . $e->getMessage();
        }
    }
}
$preselect_cat = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Topic - ForumHub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container" style="max-width: 800px; justify-content: center;">
        <div class="card" style="width: 100%;">
            <h2 style="margin-bottom: 20px;">Create a New Topic</h2>
            
            <?php if ($error): ?>
                <div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($preselect_cat == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Topic Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="255" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" id="topicContent" class="form-control" required style="min-height: 200px;"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    <div style="text-align: right; font-size: 0.8em; color: var(--muted);" id="charCount">0 characters</div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Topic</button>
            </form>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
